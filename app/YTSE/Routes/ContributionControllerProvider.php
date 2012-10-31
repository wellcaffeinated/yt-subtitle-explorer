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

class ContributionControllerProvider implements ControllerProviderInterface {

    public function connect(Application $app){

        $self = $this;
        $this->app = $app;

        $controller = $app['controllers_factory'];

        /**
         * Show contribution page for video
         */
        $controller->get('/{videoId}', function(Request $request, Application $app, $videoId){

            $video = $app['ytplaylist']->getVideoById($videoId);

            if (!$video){

                $app->abort(404, 'Video not found.');
            }

            $video['caption_details'] = array();

            if ($video['caption_links']){

                foreach ( $video['caption_links'] as $cap ){
                
                    foreach ( $video['languages'] as &$lang ){
                        if ($lang['lang_code'] === $cap['lang_code'])
                            $video['caption_details'][] = $lang;
                    }
                }
            }

            $error = $app['session']->get('last_error');
            $app['session']->remove('last_error');

            return $app['twig']->render('page-contribute.twig', array(

                'video' => $video,
                'errors' => array(
                    'file' => $request->get('error_file'),
                    'lang' => $request->get('error_lang'),
                    'token' => $request->get('error_token'),
                ),
                'error_msg' => $error,
                'success_msg' => $request->get('success_msg'),

            ));
        })->bind('contribute');

        /**
         * Download caption file 
         */
        $controller->get('/{videoId}/caption', function(Request $request, Application $app, $videoId){

            $video = $app['ytplaylist']->getVideoById($videoId);

            if (!$video){

                $app->abort(404, 'Video not found.');
            }

            $capId = $request->get('capId');
            $caption = false;

            if ($capId === 'user'){

                $data = $app['session']->get('contribute_data');

                if (!$data){

                    $app->abort(404, 'Caption not found.'); 
                }

                $content = $data['content'];

                return new Response($content, 200, array(

                    'Content-type' => 'application/octet-stream',
                    //'Content-disposition' => "attachment; filename=\"$filename\"",
                ));
            }

            foreach ( $video['caption_links'] as $cap ){

                if ($cap['lang_code'] === $capId){
                    $caption = $cap;
                    break;
                }
            }

            if (!$caption){

                $app->abort(404, 'Caption not found.'); 
            }

            $token = $app['oauth']->getValidAdminToken();

            if ($token === null){

                return $app->redirect(
                    $app['url_generator']->generate('contribute',
                        array(
                            'videoId' => $videoId,
                            'error_token' => true,
                        )
                    )
                );
            }

            $format = $request->get('fmt');
            $format = $request->get('fmt') ?: 'srt';
            $content = $app['api']->getYTCaptionContent($caption['src'], $token, $format);
            $filename = str_replace(' ', '_', 'captions_'.$capId.'_'.$video['title']. '.' .$format);

            return new Response($content, 200, array(

                'Content-type' => 'application/octet-stream',
                'Content-disposition' => "attachment; filename=\"$filename\"",
            ));

        })->bind('contribute_cap');

        /**
         * Upload a caption file
         */
        $controller->match('/{videoId}/upload', function(Request $request, Application $app, $videoId) use ($self) {

            $isGet = $request->getMethod() === 'GET';
            $data = $app['session']->get('contribute_data');
            $video = $app['ytplaylist']->getVideoById($videoId);

            if (!$video){

                $app->abort(404, 'Video not found.');
            }

            if (!$isGet){
            
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

                if ( !$app['captions']->isValidExtension($file) ){

                    // if someone tries to upload a .php file, for example... stop them.
                    $app->abort(415, "Invalid File Type.");
                }

                if ( !$app['captions']->isValidEncoding($file) ){

                    $app->abort(415, "File contains invalid characters. Please use UTF-8 encoding.");
                }

                if ( !$app['captions']->isValidSize($file) ){

                    $app->abort(413, "File too big.");
                }

                $data = array(
                    //'file' => $file,
                    'content' => file_get_contents($file->getRealPath()),
                    'lang' => $lang,
                    'format' => $app['captions']->getFileExtension($file),
                );

                $app['session']->set('contribute_data', $data);
            }

            if (!$data){

                // if there is no data to save... then redirect to start
                return $app->redirect(
                    $app['url_generator']->generate('contribute',
                        array(
                            'videoId' => $videoId,
                        )
                    )
                );
            }
            
            return $app['twig']->render('page-contribute-preview.twig', array(

                'video' => $video,
                'preview_data' => $data,

            ));
        })
        ->method('GET|POST')
        ->bind('contribute_upload');

        $controller->post('/{videoId}/finish', function(Request $request, Application $app, $videoId) use ($self) {

            $error = false;
            $data = $app['session']->get('contribute_data');
            $video = $app['ytplaylist']->getVideoById($videoId);

            if (!$video){

                $app->abort(404, 'Video not found.');
            }

            if (!$data){

                // if there is no data to save... then redirect to start
                return $app->redirect(
                    $app['url_generator']->generate('contribute',
                        array(
                            'videoId' => $videoId,
                        )
                    )
                );
            }

            $content = $data['content'];
            $format = $data['format'];
            $lang = $data['lang'];

            try {

                $app['captions']->saveCaption($content, $format, $videoId, $lang, $app['oauth']->getUserName());

            } catch (\YTSE\Captions\InvalidFileFormatException $e){

                $error = $e->getMessage();

            } catch (\Exception $e){

                $error = 'Problem uploading file.';
            }

            if (!$error){

                $app['users']->incrementUploads($app['oauth']->getUserName());
                $self->emailNotification($request, $lang, $videoId);
                $app['session']->remove('contribute_data');

            } else {

                $app['session']->set('last_error', $error);
            }

            return $app->redirect(
                $app['url_generator']->generate('contribute',
                    array(
                        'videoId' => $videoId,
                        'success_msg' => !$error,
                    )
                )
            );
        })->bind('contribute_finish');

        return $controller;
    }

    public function emailNotification(Request $req, $lang, $videoId){

        $app = $this->app;

        $config = $app['ytse.config'];

        if (!isset($config['email_notify']) || empty($config['email_notify'])) return;

        $host = $req->getHost();
        $video = $app['ytplaylist']->getVideoById($videoId);

        $app['email_notification'](
            $config['email_notify'],
            'New Translation on '.$host,
            'email-notify-submission.twig',
            array(
                'username' => $app['oauth']->getUserName(),
                'lang_code' => $lang,
                'video' => $video,
                'hostname' => $host,
            )
        );
    }
}
