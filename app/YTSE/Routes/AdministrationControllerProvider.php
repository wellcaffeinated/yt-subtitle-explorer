<?php

namespace YTSE\Routes;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdministrationControllerProvider implements ControllerProviderInterface {

	public function connect(Application $app){

		$controller = $app['controllers_factory'];

		$controller->get('/', function(Application $app){

			return 'Hello Admin.';
		});

		return $controller;
	}
}
