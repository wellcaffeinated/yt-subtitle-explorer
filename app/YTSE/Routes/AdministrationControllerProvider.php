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

		$controller->match('/', function(Request $req, Application $app){

			$action = $req->get('action');
			$error = '';
			$msg = '';

			/**
			 * View caption file
			 */
			if ($action === 'view'){

				$path = $req->get('path');
				$content = $app['captions']->getCaptionContents($path);

				if (!$content) $app->abort(404, 'Caption file not found.');

				return new Response($content, 200, array(

					'Content-type' => 'text/plain',
				));
			}

			/**
			 * Delete caption file
			 */
			if ($action === 'delete'){

				$path = $req->get('path');

				try {

					$success = $app['captions']->deleteCaption($path);

					if (!$success){

						$error = 'Problem deleting caption.';
					}

				} catch (\Exception $e){

					$success = false;
					$error = $e->getMessage();

				}				
			}

			/**
			 * Approve caption
			 */
			if (preg_match('/^approve/', $action)){

				$path = $req->get('path')? $req->get('path') : str_replace('approve:', '', $action);
				$content = $app['captions']->getCaptionContents($path);

				if (!$content) $app->abort(404, 'Caption file not found.');

				$info = $app['captions']->extractCaptionInfo($path);
				$video = $app['ytplaylist']->getVideoById($info['videoId']);

				$caption = false;

				foreach( $video['caption_links'] as $cap ){

					if ($cap['lang_code'] === $info['lang_code']){
						$caption = $cap;
						break;
					}
				}

				try{

					if (!$caption){

						$data = $app['api']->createYTCaption($app['oauth']->getValidAdminToken(), $info, $content);
						//$app['refresh.data']();

					} else {

						$data = $app['api']->updateYTCaption($caption['src'], $app['oauth']->getValidAdminToken(), $info, $content);
					}

					if ($data['draft']){

						$msg = 'The approved subtitles were added to YouTube, but are in "draft mode" and will not get displayed on your video.';
					}

					$success = $app['captions']->deleteCaption($path);

					if (!$success){

						$error = 'Problem deleting caption.';
					}

				} catch (\Exception $e){

					$error = $e->getMessage();
				}
				
			}

			return $app['twig']->render('page-admin.twig', array(

				'submissions' => $app['captions']->getSubmissions(),
				'error' => $error,
				'msg' => $msg,

			));
		})
		->method('GET|POST')
		->bind('admin_main');

		return $controller;
	}
}
