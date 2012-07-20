<?php

if (!defined('YTSE_ROOT')) die("Please run the installer by visiting: {$_SERVER['SERVER_NAME']}/install");

$installer = $app['controllers_factory'];
$installer->get('/', function() use ($app) {

    $langFile = YTSE_ROOT.'/db/languages.csv';

    $app['db']->query("DROP TABLE IF EXISTS {$app['db.tables.videos']}");
    $app['db']->query("DROP TABLE IF EXISTS {$app['db.tables.playlists']}");
    $app['db']->query("DROP TABLE IF EXISTS {$app['db.tables.languages']}");

    $app['db']->query("CREATE TABLE {$app['db.tables.videos']} (
        ytid TEXT UNIQUE,
        title TEXT,
        playlist_id TEXT,
        url TEXT,
        thumbnail TEXT,
        updated TEXT,
        published TEXT,
        position INTEGER,
        usid TEXT,
        languages TEXT
        )"
    );

    $app['db']->query("CREATE TABLE {$app['db.tables.playlists']} (
        ytid TEXT UNIQUE,
        title TEXT,
        updated TEXT,
        video_list TEXT,
        last_refresh TEXT
        )"
    );

    $app['db']->query("CREATE TABLE {$app['db.tables.languages']} (
        lang_code TEXT UNIQUE,
        lang_translated TEXT,
        lang_original TEXT
        )"
    );

    if (($handle = fopen($langFile, "r")) !== FALSE) {

        $db = $app['db'];

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {

            $db->insert($app['db.tables.languages'], array(
                'lang_code' => $row[0],
                'lang_translated' => $row[1],
                'lang_original' => $row[2]
            ));
            
        }

        fclose($handle);

    } else {

        throw new Exception('Can not find language file at '.$langFile);
    }

    return 'Installation Complete';
});

return $installer;
