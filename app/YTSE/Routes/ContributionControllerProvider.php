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

		$controller->get('/{videoId}', function(Request $request, Application $app, $videoId){

			$video = $app['ytplaylist']->getVideoById($videoId);

			if (!$video){

				$app->abort(404, 'Video not found.');
			}

			$video['caption_details'] = array_map(function($cap) use ($video) {

				foreach ( $video['languages'] as &$lang ){
					if ($lang['lang_code'] === $cap['lang_code'])
						return $lang;
				}

				return null;

			}, $video['caption_links']);

			return $app['twig']->render('page-contribute.twig', array(

				'video' => $video,
				'errors' => array(
					'file' => $request->get('error_file'),
					'lang' => $request->get('error_lang'),
				),
				'success_msg' => $request->get('success_msg'),

			));
		})->bind('contribute');

		$controller->get('/{videoId}/caption', function(Request $request, Application $app, $videoId){

			$video = $app['ytplaylist']->getVideoById($videoId);

			if (!$video){

				$app->abort(404, 'Video not found.');
			}

			$capId = $request->get('capId');
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
			$filename = str_replace(' ', '_', 'captions_'.$capId.'_'.$video['title']. '.' .$format);

			return new Response($content, 200, array(

				'Content-type' => 'application/octet-stream',
				'Content-disposition' => "attachment;filename=$filename",
			));

		})->bind('contribute_cap');

		$controller->post('/{videoId}/upload', function(Request $request, Application $app, $videoId){

			$video = $app['ytplaylist']->getVideoById($videoId);

			if (!$video){

				$app->abort(404, 'Video not found.');
			}

			$file = $request->files->get('cap_file');
			$lang = $request->get('lang_code');

			// if form data invalid, redirect with error messages
			if (empty($file) || empty($lang)){

				return $app->redirect(
					$app['url_generator']->generate('contribute',
						array(
							'videoId' => $videoId,
							'error_file' => empty($file),
							'error_lang' => empty($lang),
						)
					)
				);
			}

			try {

				$app['captions']->saveCaption($file, $videoId, $lang, $app['oauth']->getUserName());

			} catch (\YTSE\Captions\InvalidFileFormatException $e){

				$app->abort(403, 'Invalid caption file format: ' . $file->guessExtension());

			} catch (\Exception $e){

				$app->abort(500, 'Problem uploading file.');
			}

			return $app->redirect(
				$app['url_generator']->generate('contribute',
					array(
						'videoId' => $videoId,
						'success_msg' => true,
					)
				)
			);

		})->bind('contribute_upload');

		return $controller;
	}
}
