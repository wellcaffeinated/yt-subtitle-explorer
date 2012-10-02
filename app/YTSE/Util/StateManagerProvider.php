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

class StateManagerProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['state'] = $app->share(function($app){

            return new StateManager($app['db']);
        });

    }

    public function boot(Application $app){
    }
}
