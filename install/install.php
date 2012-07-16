<?php

$app->get('/install', function() use ($app) {

	$app['db']->query("CREATE TABLE {$app['db.tables']['videos']} (
		ytid TEXT,
		title TEXT,
		published TEXT,
		updated TEXT,
		usid TEXT,
		languages BLOB
		)"
	);

	return 'Installation Complete';
});
