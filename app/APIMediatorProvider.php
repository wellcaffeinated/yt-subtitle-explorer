<?php

if (!defined('YTSE_ROOT')) die('Access Denied');

require_once 'APIMediator.php';

use Silex\Application;
use Silex\ServiceProviderInterface;

class APIMediatorProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['api'] = $app->share(function($app){

            return new APIMediator();
        });
    }

    public function boot(Application $app){
    }
}
