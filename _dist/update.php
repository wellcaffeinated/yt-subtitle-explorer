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

    $userdata = array();
    
    $pending = $app['captions']->getSubmissions();
    $rejections = $app['captions_rejected']->getSubmissions();
    $approvals = $app['captions_approved']->getSubmissions();

    foreach ($approvals as $sub){

        foreach ($sub['captions'] as $cap){

            if (!isset($userdata[ $cap['user'] ])){

                $userdata[ $cap['user'] ] = array(
                    'uploads' => 0,
                    'accepted' => 0,
                    'rejected' => 0,
                );
            }

            $data = $userdata[ $cap['user'] ];
            $data['uploads']++;
            $data['accepted']++;
        }
    }

    foreach ($rejections as $sub){

        foreach ($sub['captions'] as $cap){

            if (!isset($userdata[ $cap['user'] ])){

                $userdata[ $cap['user'] ] = array(
                    'uploads' => 0,
                    'accepted' => 0,
                    'rejected' => 0,
                );
            }

            $data = $userdata[ $cap['user'] ];
            $data['uploads']++;
            $data['rejected']++;
        }
    }

    foreach ($pending as $sub){

        foreach ($sub['captions'] as $cap){

            if (!isset($userdata[ $cap['user'] ])){

                $userdata[ $cap['user'] ] = array(
                    'uploads' => 0,
                    'accepted' => 0,
                    'rejected' => 0,
                );
            }

            $data = $userdata[ $cap['user'] ];
            $data['uploads']++;
        }
    }

    foreach ($userdata as $username => $data){

        $user = $app['users']->getUser($username);
        $user->set('uploads', $data['uploads']);
        $user->set('accepted', $data['accepted']);
        $user->set('rejected', $data['rejected']);
        $app['monolog']->addDebug("saving user data: $username has (uploads {$data['uploads']}, accepted {$data['accepted']}, rejected {$data['rejected']})");
        $app['users']->saveUser($user);
    }

    unlink($app['ytse.root'].'/app/data/version.txt');
    copy($base.'/version.txt', $app['ytse.root'].'/app/data/version.txt');

    $app['maintenance_mode']->disable();
};