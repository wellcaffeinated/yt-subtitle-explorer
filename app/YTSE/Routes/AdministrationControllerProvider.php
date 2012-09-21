<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Routes;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdministrationControllerProvider implements ControllerProviderInterface {

	public function connect(Application $app){

		$controller = $app['controllers_factory'];

		/**
		 * Main admin route
		 */
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
			 * Maintenance Mode Toggle
			 */
			
			if ($action === 'maintenance'){

				if ($app['maintenance_mode']->isEnabled()){
					$app['maintenance_mode']->disable();
				} else {
					$app['maintenance_mode']->enable();
				}
			}

			/**
			 * Delete caption file
			 */
			if (preg_match('/^delete/', $action)){

				$path = $req->get('path')? $req->get('path') : str_replace('delete:', '', $action);

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

				foreach ( $video['caption_links'] as $cap ){

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

					if (array_key_exists('errors', $data)){

						foreach ($data['errors'] as $err) {
							$error .= $err['msg'] . '<br/>';
						}
					}

					$success = $app['captions']->deleteCaption($path);

					if (!$success){

						$error .= 'Problem deleting caption.';
					}

				} catch (\Exception $e){

					$error = $e->getMessage();
				}
				
			}

			/**
			 * Batch approve
			 */
			if ($action === 'batch_approve' && $req->get('selected')) {

				foreach ($req->get('selected') as $path) {

					$content = $app['captions']->getCaptionContents($path);	

					if (!$content) continue;

					$info = $app['captions']->extractCaptionInfo($path);
					$video = $app['ytplaylist']->getVideoById($info['videoId']);

					$caption = false;

					foreach ( $video['caption_links'] as $cap ){

						if ($cap['lang_code'] === $info['lang_code']){
							$caption = $cap;
							break;
						}
					}

					$batch[] = array(
						'url' => $caption? $caption['src'] : false,
						'info' => $info,
						'content' => $content,
					);
				}

				try {

					$ret = $app['api']->batchSaveCaptions($batch, $app['oauth']->getValidAdminToken());

					foreach ($ret as $key => $data) {

						$filename = $batch[$key]['info']['filename'];
						
						if (array_key_exists('errors', $data)){

							$error .= "Problem saving caption: $filename <br/>";

							foreach ($data['errors'] as $err) {
								$error .= $err['msg'] . '<br/>';
							}

						} else {

							$path = $batch[$key]['info']['path'];
							$success = $app['captions']->deleteCaption($path);

							if (!$success){

								$error .= "Problem removing caption file: $filename <br/>";
							}
						}
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

		/**
		 * Main admin route
		 */
		$controller->match('/refresh', function(Request $req, Application $app){

			$app['refresh.data']();

			return $app->redirect($app['url_generator']->generate('admin_main'));
		})->bind('admin_refresh_data');

		return $controller;
	}
}
