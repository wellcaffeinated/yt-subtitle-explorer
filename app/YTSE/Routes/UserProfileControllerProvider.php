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

        return $controller;
    }

}
