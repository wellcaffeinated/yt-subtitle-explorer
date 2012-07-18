<?php

if (!defined('YTSE_ROOT')) die('Access Denied');

require 'YTPlaylist.php';

use Silex\Application;
use Silex\ServiceProviderInterface;

class YTPlaylistProvider implements ServiceProviderInterface {
    
    public function register(Application $app)

        $id = $app['ytplaylist.id'];

        if (!$id){
            throw new Exception('Playlist ID undefined');
        }

        $app['ytplaylist'] = $app->share(function($app){

            return new YTPlaylist( $id, $app );
        });
    }

    public function boot(Application $app){
    }
}
