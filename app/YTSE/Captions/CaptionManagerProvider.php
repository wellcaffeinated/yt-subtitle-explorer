<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Captions;

use Silex\Application;
use Silex\ServiceProviderInterface;

class CaptionManagerProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['captions'] = $app->share(function($app){

            $config = $app['captions.config'];

            if ( empty($config['caption_dir']) ){

                throw "You must define a directory to hold caption uploads.";
            }

            return new CaptionManager($config['caption_dir']);
        });
    }

    public function boot(Application $app){
    }
}
