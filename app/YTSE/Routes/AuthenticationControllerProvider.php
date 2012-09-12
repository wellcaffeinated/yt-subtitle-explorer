<?php

namespace YTSE\Routes;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationControllerProvider implements ControllerProviderInterface {

	private $oauth;

	public function __construct(\YTSE\OAuth\LoginManager $oauth){

		$this->oauth = $oauth;
	}

	public function connect(Application $app){

		$self = $this;
		$data = array(
			'loggedIn' => $this->oauth->isLoggedIn(),
			'username' => $this->oauth->getUserName(),
		);

		$controller = $app['controllers_factory'];

		/**
		 * Show login page
		 */
		$controller->get('/login', function(Request $req, Application $app) use ($data) {

			return $app['twig']->render('page-login.twig', $data);

		})->bind('login');

		/**
		 * Do logout action
		 */
		$controller->get('/logout', function (Request $req, Application $app) use ($self) {

			$self->logOut();

			$page = $req->get('redirect')? $req->get('redirect') : 'search_page';

			return $app->redirect($app['url_generator']->generate($page));
		})->bind('logout');

		/**
		 * Redirect to youtube for authentication
		 */
		$controller->get('/login/authenticate', function() use ($app, $self){

			return $app->redirect($self->getAuthUrl(
				$app['url_generator']->generate('auth_callback', array(), true)
			));
		})->bind('authenticate');

		/**
		 * Redirected from youtube to complete authentication
		 */
		$controller->get('/login/authenticate/callback', function(Request $request) use ($app, $self) {

			try {
				$self->authenticate($request, $app['url_generator']->generate('auth_callback', array(), true));
			}
			catch(\Exception $e){

				$app->abort(400, 'Problem Authenticating');
			}

			if ( !$self->isLoggedIn() ) {
				$app->abort(400, 'Problem Authenticating');
			}

			$route = $app['session']->get('login_referrer');

			return $app->redirect($route? $route : $request->getBaseUrl());
		})->bind('auth_callback');

		return $controller;
	}

	public function isLoggedIn(){
		return $this->oauth->isLoggedIn();
	}

	public function logOut(){
		$this->oauth->logOut();
	}

	public function authenticate(Request $request, $redirectUri){
		
		$token = $this->oauth->getAccessToken($request, array(
			'redirect_uri' => $redirectUri
		));

		$this->oauth->authenticate( $token );
	}

	public function getAuthUrl($redirect_url){
		return $this->oauth->getAuthUrl( $redirect_url );
	}
}
