<?php

namespace YTSE\OAuth;

use Illuminate\Socialite\OAuthTwo\StateStoreInterface;

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