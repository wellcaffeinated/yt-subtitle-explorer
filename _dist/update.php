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

    // copy app changes
    FileSystemManager::rcopy($base.'/app', $app['ytse.root'].'/app');
    FileSystemManager::rcopy($base.'/library', $app['ytse.root'].'/library');
    // copy($base.'/index.php', $app['ytse.root'].'/index.php');

    // clear cache
    $perms = fileperms($app['ytse.root'].'/cache');
    FileSystemManager::rrmdir($app['ytse.root'].'/cache');
    mkdir($app['ytse.root'].'/cache', $perms);

    $app['maintenance_mode']->disable();
};