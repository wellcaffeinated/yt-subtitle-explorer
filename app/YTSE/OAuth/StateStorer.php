<?php

namespace YTSE\OAuth;

use Illuminate\Socialite\OAuthTwo\StateStoreInterface;

class StateStorer implements StateStoreInterface {

    private $session;

	function StateStorer(\Symfony\Component\HttpFoundation\Session\Session $session){

		$this->session = $session;
	}

    /**
     * Get the state from storage.
     *
     * @return string
     */
    public function getState(){

        return $this->session->get('state');
    }

    /**
     * Set the state in storage.
     *
     * @param  string  $state
     * @return void
     */
    public function setState($state){

        $this->session->set('state', $state);
    }

}