<?php

use \Heartsentwined\FileSystemManager\FileSystemManager;

return function($app){

    $base = __DIR__;
    
    if (is_dir($app['ytse.root'])){

        // enable maintenance mode
        $app['maintenance_mode']->enable();
        
        // copy app changes
        FileSystemManager::rcopy($base.'/app', $app['ytse.root'].'/app');

        // clear cache
        $perms = fileperms($app['ytse.root'].'/cache');
        FileSystemManager::rrmdir($app['ytse.root'].'/cache');
        mkdir($app['ytse.root'].'/cache', $perms);

        // disable maintenance mode
        $app['maintenance_mode']->disable();
    }
};