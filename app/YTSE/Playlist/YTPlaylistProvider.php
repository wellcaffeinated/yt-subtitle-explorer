<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Playlist;

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
