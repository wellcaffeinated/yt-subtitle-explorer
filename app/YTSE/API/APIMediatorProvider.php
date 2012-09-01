<?php

namespace YTSE\API;

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
