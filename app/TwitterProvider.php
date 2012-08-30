<?php

if (!defined('YTSE_ROOT')) die('Access Denied');

use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\Response;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Socialite\OAuthTwo\OAuthTwoProvider;
use Illuminate\Socialite\OAuthTwo\StateStoreInterface;

class TwitterOAuth extends OAuthTwoProvider {

    /**
     * The scope delimiter.
     *
     * @var string
     */
    protected $scopeDelimiter = ',';

    /**
     * Get the auth end-point URL for a provider.
     *
     * @return string
     */
    protected function getAuthEndpoint()
    {
        return 'https://api.twitter.com/oauth/authorize';
    }

    /**
     * Get the access token end-point URL for a provider.
     *
     * @return string
     */
    protected function getAccessEndpoint()
    {
        return 'https://api.twitter.com/oauth/access_token';
    }

    /**
     * Get the user data end-point URL for the provider.
     *
     * @return string
     */
    protected function getUserDataEndpoint()
    {
        return '';
    }

    /**
     * Execute the request to get the access token.
     *
     * @param  Guzzle\Http\ClientInterface  $client
     * @param  array  $options
     * @return Guzzle\Http\Message\Response
     */
    // protected function executeAccessRequest(ClientInterface $client, $options)
    // {
    //     return $client->post($this->getAccessEndpoint(), null, $options)->send();
    // }

    /**
     * Determine if there is a state mismatch.
     *
     * @param  Symfony\Component\HttpFoundation\Request  $request
     * @return bool
     */
    // protected function stateMismatch(Request $request)
    // {
    //     return false;
    // }

    /**
     * Get the default scopes for the provider.
     *
     * @return array
     */
    // public function getDefaultScope()
    // {
    //     return array();
    // }

}

class StateStorer implements StateStoreInterface {

    /**
     * Get the state from storage.
     *
     * @return string
     */
    public function getState(){

        return $app['session']->get('twitter.state');
    }

    /**
     * Set the state in storage.
     *
     * @param  string  $state
     * @return void
     */
    public function setState($state){

        $app['session']->set('twitter.state', $state);
    }

}

use Silex\Application;
use Silex\ServiceProviderInterface;

class TwitterProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['twitter.oauth'] = $app->share(function($app){

            $key = $app['twitter.consumer_key'];
            $secret = $app['twitter.consumer_secret'];

            if (!$key || !$secret){
                throw new Exception('Please define twitter.consumer_key and twitter.consumer_secret');
            }

            return new TwitterOAuth( new StateStorer(), $key, $secret );
        });
    }

    public function boot(Application $app){
    }
}
