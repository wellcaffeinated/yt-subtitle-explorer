<?php

namespace YTSE\OAuth;

use Illuminate\Socialite\OAuthTwo\GoogleProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class GoogleOAuthProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['google.oauth'] = $app->share(function($app){

            $key = $app['google.consumer_key'];
            $secret = $app['google.consumer_secret'];

            if (!$key || !$secret){
                throw new Exception('Please define google.consumer_key and google.consumer_secret');
            }

            return new GoogleProvider( new YTSE\OAuth\StateStorer($app), $key, $secret );
        });
    }

    public function boot(Application $app){
    }
}
