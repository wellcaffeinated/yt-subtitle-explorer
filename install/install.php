<?php

$installer = $app['controllers_factory'];
$installer->get('/', function() use ($app) {

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

return $installer;
