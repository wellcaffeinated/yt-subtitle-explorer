<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Users;

use Silex\Application;
use Silex\ServiceProviderInterface;

class UserManagerProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['users'] = $app->share(function($app){

            return new UserManager($app['db']);
        });
    }

    public function boot(Application $app){
    }
}
