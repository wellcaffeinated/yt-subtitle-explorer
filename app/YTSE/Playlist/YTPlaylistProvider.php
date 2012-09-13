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

            $config = $app['ytplaylist.config'];

            return new YTPlaylist( $config['playlist'], $app['db'], $config['lang_file'], $config['default_lang'] );
        });
    }

    public function boot(Application $app){
    }
}
