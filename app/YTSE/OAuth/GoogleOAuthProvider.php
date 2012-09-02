<?php

namespace YTSE\OAuth;

use Illuminate\Socialite\OAuthTwo\GoogleProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class OAuthProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['oauth'] = $app->share(function($app){

            if ( array_key_exists('google', $app['oauth.config']) ){

                $config = $app['oauth.config']['google'];

                if (!array_key_exists('key', $config) || !array_key_exists('secret', $config))
                    throw new \Exception('Please define oauth.config credentials.');

                return new GoogleProvider( new YTSE\OAuth\StateStorer($app), $config['key'], $config['secret'] );
            }
            
            throw new \Exception('Unsupported OAuth Provider.');
        });
    }

    public function boot(Application $app){
    }
}
