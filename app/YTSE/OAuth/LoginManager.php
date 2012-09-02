<?php

namespace YTSE\OAuth;

use Illuminate\Socialite\OAuthTwo\GoogleProvider;

class LoginManager extends GoogleProvider {

	private $session;
	private $admin;

	public function LoginManager(\Symfony\Component\HttpFoundation\Session\Session $session, $key, $secret){

		$this->session = $session;
		parent::__construct(new StateStorer($session), $key, $secret);
	}
	
	public function isAuthorized(){

		$username = $this->session->get('username');
		$admin = $this->getAdmin();

		return ( $this->isLoggedIn() && $admin !== null && $username === $admin );
	}

	public function setAdmin($name){

		$this->admin = $name;
	}

	public function getAdmin(){

		return $this->admin;
	}

	public function isLoggedIn(){

		return ( null !== $this->session->get('username') );
	}

	public function logOut(){

		$this->session->set('username', null);
	}

	public function getUserName(){
		
		return $this->session->get('username');
	}
}
