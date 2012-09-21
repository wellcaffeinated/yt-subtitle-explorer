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

        $config = $app['captions.config'];

        if ( empty($config['caption_dir']) ){

            throw "You must define a directory to hold caption uploads.";
        }

        $basedir = preg_replace('/\/$/', '', $config['caption_dir']);

        $app['captions'] = $app->share(function($app) use ($basedir) {

            return new CaptionManager($basedir . '/submissions');
        });

        $app['captions_rejected'] = $app->share(function($app) use ($basedir) {

            return new CaptionManager($basedir . '/rejected');
        });

        $app['captions_approved'] = $app->share(function($app) use ($basedir) {

            return new CaptionManager($basedir . '/approved');
        });
    }

    public function boot(Application $app){
    }
}
