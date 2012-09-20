<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\API;

use Silex\Application;
use Silex\ServiceProviderInterface;

class APIMediatorProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['api'] = $app->share(function($app){

        	$config = $app['api.config'];

            return new APIMediator($config['yt.api.key'], $config['thumbnail']);
        });
    }

    public function boot(Application $app){
    }
}
