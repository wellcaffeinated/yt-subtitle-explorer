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
	$app->mount('/install', include YTSE_ROOT.'/install/install.php');
	$app->run();
	exit;
}

$app->get('/hello', function() {
    return 'Hello!';
});

$app->get('/create/{id}', function($id) use ($app){

	$app['db']->insert($app['db.tables']['videos'], array(
		'ytid'=>$id, 
		'title'=>'Hello World of DB'
	));

	return 'created';

})->value('id', 42);

$app->get('/read/{id}', function($id) use ($app){

	$sql = "SELECT * FROM {$app['db.tables']['videos']}". ($id? " WHERE ytid = ?" : '');
	$ret = $app['db']->fetchAssoc($sql, array($id));
	return "<p>ID: {$ret['ytid']}<br/>Title: {$ret['title']}</p>";
})->value('id', 42);

$app->run();