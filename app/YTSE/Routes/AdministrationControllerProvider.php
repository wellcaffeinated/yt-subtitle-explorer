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

			return $app['twig']->render('page-admin.twig', array(

				'submissions' => $app['captions']->getSubmissions(),

			));
		});

		$controller->get('/caption', function(Request $req, Application $app){

			$path = $req->get('path');
			$content = '';

			if ($path){

				try{

					$content = file_get_contents($path);
				} catch (\Exception $e) {}
			}

			return new Response($content, 200, array(

				'Content-type' => 'text/plain',
			));

		})->bind('caption_contents');

		return $controller;
	}
}
