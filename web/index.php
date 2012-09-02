<?php

require_once __DIR__.'/../vendor/autoload.php';

define('YTSE_ROOT', __DIR__.'/..');
require YTSE_ROOT.'/config.php';

$app = new Silex\Application();
$app['debug'] = defined('DEBUG');

$app['db.tables.videos'] = YTSE_DB_PFX.'videos';
$app['db.tables.playlists'] = YTSE_DB_PFX.'playlists';
$app['db.tables.languages'] = YTSE_DB_PFX.'languages';

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
    	'driver'   => 'pdo_sqlite',
        'path'     => YTSE_DB_PATH,
    ),
));

if (false and is_dir(YTSE_ROOT.'/install')){

	// do installation
	$app->mount('/install', include YTSE_ROOT.'/app/install/install.php');
	$app->run();
	exit;
}

// register api mediator provider
$app->register(new YTSE\API\APIMediatorProvider());
// register playlist provider
$app->register(new YTSE\Playlist\YTPlaylistProvider(), array(
    'ytplaylist.id' => YT_PLAYLIST
));
$app->register(new YTSE\OAuth\OAuthProvider(), array(
	'oauth.config' => array(
		'provider' => 'google',
		'key' => G_OAUTH_KEY,
		'secret' => G_OAUTH_SECRET,
		'admin' => ADMIN_YT_USERNAME,
	)
));

// register twig templating
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => YTSE_ROOT.'/app/views'
));
// url service provider
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
// register the session extension
$app->register(new Silex\Provider\SessionServiceProvider(), array(
	'session.storage.options' => array(
		'secure' => true
	)
));

$app['refresh.data'] = $app->protect(function() use ($app) {

	$pl = $app['ytplaylist'];

	$data = $app['api']->getYTPlaylist($pl->getId());

	if (!$data){

		return false;
	}

	$ids = array();

	foreach ($data['videos'] as &$video){

		$ids[] = $video['ytid'];
	}

	$allLangs = $app['api']->getYTLanguages($ids);

	foreach ($data['videos'] as &$video){

		if (array_key_exists($video['ytid'], $allLangs))
			$video['languages'] = $allLangs[ $video['ytid'] ];
	}

	try {

		$pl->setData($data);
		$pl->syncLocal();
		
	} catch (\Exception $e){
		// don't care for now
	}
});

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$checkAuthorization = function(Request $req, Silex\Application $app){

	// if you are not the administrator, get lost (or login)
	if ( !$app['oauth']->isAuthorized() ){
		
		return $app->abort(401, "You are not authorized.");
	}
};

$checkAuthentication = function(Request $req, Silex\Application $app){

	if ( !$app['oauth']->isLoggedIn() ){

		$app['session']->set('login_referrer', $req->getRequestUri());

		// redirect to login page
		return new \Symfony\Component\HttpFoundation\RedirectResponse(
			$app['url_generator']->generate('login')
		);
	}
};

/**
 * Before routing
 */
$app->before(function(Request $request) use ($app) {

	$pl = $app['ytplaylist'];

	// check to see if we need an update from remote
	if (!$pl->hasData() || $request->get('refresh') === 'true'){

		// start update process
		$app['refresh.data']();
	}
	
});

/**
 * Main Site
 */
$app->get('/', function(Silex\Application $app) {
	
	return $app['twig']->render('page-video-search.twig', array(
	
		'videos' => $app['ytplaylist']->getVideos()
	));
});

/**
 * Language Data
 */
$app->mount('/videos', new YTSE\Routes\LanguageDataControllerProvider());

/**
 * OAuth Authentication
 */

$app->mount('/', new YTSE\Routes\AuthenticationControllerProvider( $app['oauth'] ));

/**
 * Contributions
 */
$contrib = new YTSE\Routes\ContributionControllerProvider();
$contrib = $contrib->connect($app);
$contrib->before($checkAuthentication);
$app->mount('/contribute', $contrib);

/**
 * Administration
 */

$admin = new YTSE\Routes\AdministrationControllerProvider();
$admin = $admin->connect($app);
$admin->before($checkAuthentication);
$admin->before($checkAuthorization);
$app->mount('/admin', $admin);

/**
 * OAuth test
 */
//$app->mount('/admin', include YTSE_ROOT.'/app/Routes/oauth.php');

/**
 * After response is sent
 */
$app->finish(function(Request $request, Response $response) use ($app) {

	$pl = $app['ytplaylist'];

	// check to see if we need an update from remote
	if ($pl->isDirty()){

		// start update process
		$app['refresh.data']();
	}

});


/**
 * Start App
 */
$app->run();
