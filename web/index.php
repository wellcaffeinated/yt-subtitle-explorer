<?php

require_once __DIR__.'/../vendor/autoload.php';

define('YTSE_ROOT', __DIR__.'/..');
require YTSE_ROOT.'/config.php';

global $app;
$app = new Silex\Application();
$app['debug'] = defined('DEBUG');

$app['db.tables.videos'] = YTSE_DB_PFX.'videos';
$app['db.tables.playlists'] = YTSE_DB_PFX.'playlists';

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => YTSE_DB_PATH,
    ),
));

if (false and is_dir(YTSE_ROOT.'/install')){

	// do installation
	$app->mount('/install', include YTSE_ROOT.'/install/install.php');
	$app->run();
	exit;
}

require YTSE_ROOT.'/app/APIMediatorProvider.php';
require YTSE_ROOT.'/app/YTPlaylistProvider.php';

// register api mediator provider
$app->register(new APIMediatorProvider());
// register playlist provider
$app->register(new YTPlaylistProvider(), array(
    'ytplaylist.id' => '908547EAA7E4AE74'
));
// register twig templating
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => YTSE_ROOT.'/views'
));

$app['refresh.data'] = $app->protect(function() use ($app) {

	$pl = $app['ytplaylist'];

	$data = $app['api']->getYTPlaylist($pl->getId());

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
$app->get('/', function(Silex\Application $app) {
	
	return $app['twig']->render('all.twig', array(

		'videos' => $app['ytplaylist']->getVideos()
	));
});

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
