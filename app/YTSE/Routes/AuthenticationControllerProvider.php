<?php

namespace YTSE\Routes;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationControllerProvider implements ControllerProviderInterface {

	private $oauth;

	public function AuthenticationControllerProvider(\YTSE\OAuth\LoginManager $oauth){

		$this->oauth = $oauth;
	}

	public function connect(Application $app){

		$self = $this;
		$data = array(
			'loggedIn' => $this->oauth->isLoggedIn(),
			'username' => $this->oauth->getUserName(),
		);

		$controller = $app['controllers_factory'];

		$controller->get('/login', function(Request $req, Application $app) use ($data) {

			return $app['twig']->render('page-login.twig', $data);

		})->bind('login');

		$controller->get('/logout', function () use ($app, $self) {

			$self->logOut();

			return $app->redirect($app['url_generator']->generate('login'));
		})->bind('logout');

		$controller->get('/login/authenticate', function() use ($app){

			
		})->bind('authenticate');

		$controller->get('/login/authenticate/callback', function() use ($app) {

			$route = $req->get('route');

			return $app->redirect($route? $route : '/');
		})->bind('auth_callback');

		return $controller;
	}

	public function logOut(){

		$this->oauth->logOut();
	}
}
