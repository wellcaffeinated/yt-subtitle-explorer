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

class MaintenanceModeProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['maintenance_mode'] = $app->share(function($app){

            return new MaintenanceModeManager($app['maintenance_mode.options']['base_dir']);
        });

    }

    public function boot(Application $app){
    }
}
