<?php

namespace YTSE\Routes;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContributionControllerProvider implements ControllerProviderInterface {

	public function connect(Application $app){

		$controller = $app['controllers_factory'];

		$controller->get('/{videoId}', function(Application $app, $videoId){

			$video = $app['ytplaylist']->getVideoById($videoId);

			if (!$video){
				
				$app->abort(404, 'Video not found.');
			}

			return $app['twig']->render('page-contribute.twig', array(

				'video' => $video,

			));
		})->bind('contribute');

		return $controller;
	}
}
