<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Util;

use Silex\Application;
use Silex\ServiceProviderInterface;

class AutoUpdateProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['ytse_version'] = $app->share(function($app){

            return file_get_contents($app['ytse.root'].'/app/data/version.txt');
        });

        $app['auto_update'] = $app->share(function($app){

            return new AutoUpdater($app['ytse_version']);
        });
    }

    public function boot(Application $app){
    }
}
