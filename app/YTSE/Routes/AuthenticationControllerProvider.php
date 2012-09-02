<?php

namespace YTSE\Routes;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationControllerProvider implements ControllerProviderInterface {

	public function connect(Application $app){

		$controller = $app['controllers_factory'];

		$controller->get('/login', function(Application $app){

			return 'Please login.';
		})->bind('login');

		return $controller;
	}
}
