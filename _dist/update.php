<?php

use \Heartsentwined\FileSystemManager\FileSystemManager;

// // enable maintenance mode
// $app['maintenance_mode']->enable();

// // copy app changes
// FileSystemManager::rcopy($base.'/app', $app['ytse.root'].'/app');

// // clear cache
// $perms = fileperms($app['ytse.root'].'/cache');
// FileSystemManager::rrmdir($app['ytse.root'].'/cache');
// mkdir($app['ytse.root'].'/cache', $perms);

// // disable maintenance mode
// $app['maintenance_mode']->disable();

return function($app){

    $base = __DIR__;

    $app['maintenance_mode']->enable();

    FileSystemManager::rcopy($base.'/app', $app['ytse.root'].'/app');

    $app['db']->query("DELETE FROM {$this->tables['languages']}");

    if (($handle = fopen($app['ytplaylist.config']['lang_file'], "r")) !== FALSE) {

        $langs = 'ytse_languages';

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {

            $app['db']->insert($langs, array(
                'lang_code' => $row[0],
                'lang_translated' => $row[1],
                'lang_original' => $row[2]
            ));
        }

        fclose($handle);

    }

    $app['maintenance_mode']->disable();
};