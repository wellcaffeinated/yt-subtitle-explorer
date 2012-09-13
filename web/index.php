<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

require_once __DIR__.'/../vendor/autoload.php';

define('YTSE_ROOT', __DIR__.'/..');
require_once YTSE_ROOT.'/config.php';

$app = new Silex\Application();
$app['debug'] = defined('DEBUG');

$app['db.tables.videos'] = YTSE_DB_PFX.'videos';
$app['db.tables.playlists'] = YTSE_DB_PFX.'playlists';
$app['db.tables.languages'] = YTSE_DB_PFX.'languages';
define('YTSE_DB_ADMIN_TABLE', YTSE_DB_PFX.'admin');

// doctrine for db functions
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
    	'driver'   => 'pdo_sqlite',
        'path'     => YTSE_DB_PATH,
    ),
));

// url service provider
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
// register the session extension
$app->register(new Silex\Provider\SessionServiceProvider(), array(
	'session.storage.options' => array(
		'secure' => true
	)
));
// register twig templating
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => YTSE_ROOT.'/app/views',
    'twig.options' => array(
    	'cache' => YTSE_ROOT.'/cache/',
    ),
));

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
// caption manager
$app->register(new YTSE\Captions\CaptionManagerProvider(), array(
    'captions.config' => array(
    	'caption_dir' => CAPTION_DIR,
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
	$capData = $app['api']->getYTCaptions($ids, $app['oauth']->getValidAdminToken());

	foreach ($data['videos'] as &$video){

		if (array_key_exists($video['ytid'], $allLangs))
			$video['languages'] = $allLangs[ $video['ytid'] ];

		if (array_key_exists($video['ytid'], $capData))
			$video['caption_links'] = $capData[ $video['ytid'] ];
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


/**
 * Error handling
 */
$app->error(function (\Exception $e, $code) use ($app) {

    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        case 500:
            $message = 'We are sorry, but something went terribly wrong. Try again later.';
            break;
        default:
        	$message = $e->getMessage();
    }

    return $app['twig']->render('page-error-msg.twig', array(
	
		'msg' => $message,
	));
});

/**
 * OAuth Authentication
 */

$app->mount('/', new YTSE\Routes\AuthenticationControllerProvider( $app['oauth'] ));

function dbDefOk($conn){
	$schema = $conn->getSchemaManager();
	return $schema->tablesExist(YTSE_DB_ADMIN_TABLE);
}

if (!dbDefOk($app['db']) || !$app['oauth']->adminTokenAvailable()){

	// do installation
	$app->mount('/', new YTSE\Routes\InstallationControllerProvider());
	$app->run();
	exit;
}

/**
 * Check if the user has admin authorization (the only kind)
 */
$checkAuthorization = function(Request $req, Silex\Application $app){

	if ( !$app['oauth']->hasYoutubeAuth() ){

		$app['oauth']->doYoutubeAuth();
		$app['session']->set('login_referrer', $req->getRequestUri());

		return new \Symfony\Component\HttpFoundation\RedirectResponse(
			$app['url_generator']->generate('authenticate')
		);
	}

	// if you are not the administrator, get lost
	if ( !$app['oauth']->isAuthorized() ){
		
		return $app->abort(401, "You are not authorized. Please log out and try again.");
	}
};

/**
 * Check it the user has been authenticated with google
 */
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
 * Enable app to access user's youtube data when authenticated
 */
$needYoutubeAuth = function(Request $req, Silex\Application $app){

	$app['oauth']->doYoutubeAuth();
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
})->bind('search_page');

/**
 * Language Data
 */
$app->mount('/videos', new YTSE\Routes\LanguageDataControllerProvider());

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
$admin->before($needYoutubeAuth);
$admin->before($checkAuthentication);
$admin->before($checkAuthorization);
$app->mount('/admin', $admin);

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
