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

		$controller->get('/{videoId}/caption/{capId}', function(Request $request, Application $app, $videoId, $capId){

			$video = $app['ytplaylist']->getVideoById($videoId);

			if (!$video){

				$app->abort(404, 'Video not found.');
			}

			$caption = false;

			foreach( $video['caption_links'] as $cap ){

				if ($cap['lang_code'] === $capId)
					$caption = $cap;
			}

			if (!$caption){

				$app->abort(404, 'Caption not found.');	
			}

			$format = $request->get('format');
			$format = $request->get('format') ?: 'srt';
			$content = $app['api']->getYTCaptionContent($caption['src'], $app['oauth']->getValidAdminToken(), $format);
			$filename = str_replace(' ', '_', 'captions_'.$capId.'_'.$video['title'].$format);

			return new Response($content, 200, array(
				'Content-type' => 'application/octet-stream',
				'Content-disposition' => "attachment;filename=$filename",
			));

		})->bind('contribute_cap');

		return $controller;
	}
}
