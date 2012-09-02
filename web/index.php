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
$app->register(new YTSE\OAuth\GoogleOAuthProvider(), array(
	'google.consumer_key' => G_OAUTH_KEY,
	'google.consumer_secret' => G_OAUTH_SECRET
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

	foreach ($data['videos'] as &$video){

		$langs = $app['api']->getYTLanguages($video['ytid']);

		// place default lang first
		usort($langs, function($a, $b){

			if ($a['lang_default'] === 'true'){
				return -1;
			}

			if ($b['lang_default'] === 'true'){
				return 1;
			}

			return 0;
		});

		$video['languages'] = $langs;
	}

	$pl->setData($data);
	$pl->syncLocal();

});

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$langListRegExp = '^([a-zA-Z]{2}([-][a-zA-Z]{2})?(~|$))*';
$convertLangList = function($list, Request $request){

	$langs = array_filter(array_unique(explode('~', $list)));

	if (empty($langs)){
		return array('en');
	}

	return $langs;
};

/**
 * Before routing
 *****************/
$app->before(function(Request $request) use ($app) {

	$pl = $app['ytplaylist'];

	// check to see if we need an update from remote
	if (!$pl->hasData() || $request->get('refresh') === 'true'){

		// start update process
		$app['refresh.data']();
	}
	
});

/**
 * Routing
 */

// all videos
$app->get('/', function(Silex\Application $app) {
	
	$vids = $app['twig']->render('videolist.twig', array(

		'videos' => $app['ytplaylist']->getVideos()
	));

	return $app['twig']->render('all.twig', array(
	
		'body' => $vids
	));
});

// meta data for languages (name, code, ...)
$app->get('/languages/meta', function( Silex\Application $app, Request $request ) {

	$q = $request->get('q').'%';

	if (!$q){

		$data = $app['db']->fetchAll("SELECT * FROM {$app['db.tables.languages']}");

	} else {

		$data = $app['db']->fetchAll(
			"SELECT * FROM {$app['db.tables.languages']} WHERE lang_original LIKE ? OR lang_translated LIKE ?",
			array($q, $q)
		);
	}
	
	return $app->json($data);
})->bind('langmeta');

$app->get('/languages/all', function( Silex\Application $app ) {
	
	return $app['twig']->render('videolist.twig', array(

		'videos' => $app['ytplaylist']->getVideos()
	));
})->bind('langall');

// find videos filtered by languages provided
$app->get('/languages/{withWithout}/{anyEvery}/{lang_list}', function( $withWithout, $anyEvery, array $lang_list, Silex\Application $app ) {
	
	return $app['twig']->render('videolist.twig', array(

		'videos' => $app['ytplaylist']->getVideosFilterLang( $lang_list, array('type' => $anyEvery, 'negate' => $withWithout === 'with') )
	));
})
->bind('langfilter')
->assert('lang_list', $langListRegExp)
->assert('withWithout', '(with|without)')
->assert('anyEvery', '(any|every)')
->convert('lang_list', $convertLangList);

/**
 * OAuth test
 */
$app->mount('/admin', include YTSE_ROOT.'/app/routes/oauth.php');

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
