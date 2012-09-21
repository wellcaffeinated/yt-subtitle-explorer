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

class UserProfileControllerProvider implements ControllerProviderInterface {

    public function connect(Application $app){

        $self = $this;
        $this->app = $app;

        $controller = $app['controllers_factory'];

        $controller->match('/', function(Request $req, Application $app){

            $user = $app['users']->getUser($app['oauth']->getUserName());
            $settings = $user->getUserSettings();

            if ($req->getMethod() === 'POST'){

                $settings['email_notifications'] = !!$req->get('notifications');
                $user->setUserSettings($settings);

                $app['users']->saveUser($user);
            }

            return $app['twig']->render('page-user-settings.twig', array(

                'user_settings' => $settings,
            ));
        })
        ->method('GET|POST')
        ->bind('user_profile_settings');

        $controller->get('/captions', function(Request $req, Application $app){

            $ctx = $req->get('ctx');
            $lang = $req->get('l');
            $videoId = $req->get('v');
            $filename = $req->get('f');
            $unsafe = '/(\/|\.\.)+/';

            if (!$ctx || !$lang || !$videoId || !$filename){

                $app->abort(404, 'Page not found.');
            }

            if (preg_match($unsafe, $lang) || preg_match($unsafe, $videoId) || preg_match($unsafe, $filename)){

                $app['monolog']->addInfo('WARNING: suspicious query parameters for user caption retrieval ($lang, $videoId, $filename) = '. "($lang, $videoId, $filename)");
                $app->abort(400, 'Bad Request.');
            }

            $ctx = ($ctx === 'approved') ? $app['captions_approved'] : $app['captions_rejected'];

            $path = $ctx->getCaptionPath($videoId, $lang, true) . '/' . $filename;
            $info = $ctx->extractCaptionInfo($path);

            if ($info['user'] !== $app['oauth']->getUserName()){

                $app->abort(404, 'Page not found.');
            }

            $content = $ctx->getCaptionContents($path);

            if (!$content) $app->abort(404, 'Page not found.');

            return new Response($content, 200, array(

                'Content-type' => 'application/octet-stream',
                'Content-disposition' => "attachment; filename=\"$filename\"",
            ));

        })->bind('user_profile_caption');

        return $controller;
    }

}
