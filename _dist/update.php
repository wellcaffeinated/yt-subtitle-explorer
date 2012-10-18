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

    $userManager = $app['users'];

    // sanity checks
    if (!$userManager) return false;
    $ytusername = $app['oauth']->getYTUserName();
    
    if (!$ytusername) throw new \Exception('Problem getting admin data.');

    $app['maintenance_mode']->enable();

    // update database
    $app['db']->executeQuery('ALTER TABLE ytse_users ADD COLUMN ytusername TEXT');

    // update admin data
    $user = $userManager->getUser($app['oauth']->getUserName());
    $ret = $app['db']->update('ytse_users', array('ytusername', $ytusername), array('username', $user->getUserName()));

    if (!$ret) throw new \Exception('Problem updating admin data.');

    // // copy app changes
    FileSystemManager::rcopy($base.'/app', $app['ytse.root'].'/app');

    // clear cache
    // $perms = fileperms($app['ytse.root'].'/cache');
    // FileSystemManager::rrmdir($app['ytse.root'].'/cache');
    // mkdir($app['ytse.root'].'/cache', $perms);

    $app['maintenance_mode']->disable();
};