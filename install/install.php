<?php

$installer = $app['controllers_factory'];
$installer->get('/', function() use ($app) {

	$app['db']->query("DROP TABLE IF EXISTS {$app['db.tables.videos']}");
	$app['db']->query("DROP TABLE IF EXISTS {$app['db.tables.playlists']}");

	$app['db']->query("CREATE TABLE {$app['db.tables.videos']} (
		ytid TEXT,
		title TEXT,
		playlist_id TEXT,
		url TEXT,
		thumbnail TEXT,
		updated TEXT,
		published TEXT,
		position INTEGER,
		usid TEXT,
		languages BLOB
		)"
	);

	$app['db']->query("CREATE TABLE {$app['db.tables.playlists']} (
		ytid TEXT,
		title TEXT,
		updated TEXT,
		last_refresh TEXT
		)"
	);

	return 'Installation Complete';
});

return $installer;
