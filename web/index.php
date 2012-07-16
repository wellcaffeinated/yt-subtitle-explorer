<?php

require_once __DIR__.'/../vendor/autoload.php';

define('YTSE_ROOT', __DIR__.'/..');
require YTSE_ROOT.'/config.php';

global $app;
$app = new Silex\Application();
$app['debug'] = defined('DEBUG');

$app['db.tables'] = array(
	 'videos' => YTSE_DB_PFX.'videos'
);

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => YTSE_DB_PATH,
    ),
));

if (false and is_dir(YTSE_ROOT.'/install')){

	// do installation
	require YTSE_ROOT.'/install/install.php';
	$app->run();
	exit;
}

$app->get('/hello', function() {
    return 'Hello!';
});

$app->get('create', function() use ($app){

	$sql = "INSERT INTO {$app['db.tables']['videos']} (ytid, title) VALUES (?, ?)";
	$app['db']->executeQuery($sql, array(42, 'Hello World of DB'));
	return 'created';
});

$app->get('read', function() use ($app){

	$sql = "SELECT * FROM {$app['db.tables']['videos']} WHERE ytid = ?";
	$ret = $app['db']->fetchAssoc($sql, array(42));
	return "<p>ID: {$ret['ytid']}<br/>Title: {$ret['title']}</p>";
});

$app->run();
