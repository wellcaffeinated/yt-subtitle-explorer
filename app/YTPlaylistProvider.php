<?php

if (!defined('YTSE_ROOT')) die('Access Denied');

require_once 'YTPlaylist.php';

use Silex\Application;
use Silex\ServiceProviderInterface;

class YTPlaylistProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['ytplaylist'] = $app->share(function($app){

            $id = $app['ytplaylist.id'];

            if (!$id){
                throw new Exception('Playlist ID undefined');
            }

            return new YTPlaylist( $id, $app );
        });
    }

    public function boot(Application $app){
    }
}
