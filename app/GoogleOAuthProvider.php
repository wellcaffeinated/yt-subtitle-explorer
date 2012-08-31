<?php

if (!defined('YTSE_ROOT')) die('Access Denied');

use Illuminate\Socialite\OAuthTwo\GoogleProvider;
use Illuminate\Socialite\OAuthTwo\StateStoreInterface;

use Silex\Application;
use Silex\ServiceProviderInterface;

class StateStorer implements StateStoreInterface {

	function StateStorer(Application $app){

		$this->app = $app;
	}

    /**
     * Get the state from storage.
     *
     * @return string
     */
    public function getState(){

        return $this->app['session']->get('twitter.state');
    }

    /**
     * Set the state in storage.
     *
     * @param  string  $state
     * @return void
     */
    public function setState($state){

        $this->app['session']->set('twitter.state', $state);
    }

}

class GoogleOAuthProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['google.oauth'] = $app->share(function($app){

            $key = $app['google.consumer_key'];
            $secret = $app['google.consumer_secret'];

            if (!$key || !$secret){
                throw new Exception('Please define google.consumer_key and google.consumer_secret');
            }

            return new GoogleProvider( new StateStorer($app), $key, $secret );
        });
    }

    public function boot(Application $app){
    }
}
