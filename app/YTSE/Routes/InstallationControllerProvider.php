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

class InstallationControllerProvider implements ControllerProviderInterface {

    public function connect(Application $app){

        $controller = $app['controllers_factory'];

        /**
         * Show installation message on main page
         */
        $controller->get('/', function() use ($app){

            $app->abort(412, 'Please complete installation by visiting: ' . $app['url_generator']->generate('install'));
        })->bind('search_page');

        /**
         * Begin install
         */
        $controller->get('/install', function(Request $req) use ($app){

            if ( !$app['oauth']->isLoggedIn() ){
                
                $app['oauth']->doYoutubeAuth();
                $app['session']->set('login_referrer', $req->getRequestUri());

                return $app['twig']->render('page-info-msg.twig', array(
    
                    'msg' => 'You need to login first. Click "Ok" to begin logging in.',
                    'ok_action' => $app['url_generator']->generate('authenticate'),
                ));

            } else if ( !$app['oauth']->isAuthorized() ){
                
                return $app->abort(401, "You are not authorized to complete the installation. Check your admin youtube username setting in the config file.");
            }

            return $app->redirect($app['url_generator']->generate('install_complete'));

        })->bind('install');

        /**
         * Actually do install
         */
        $controller->get('/install/complete', function() use ($app) {

            if ( !$app['oauth']->isAuthorized() ){
                
                return $app->abort(401, "You are not authorized to complete the installation. Check your admin youtube username setting in the config file.");
            }

            $app['ytplaylist']->initDb(true);
            $app['oauth']->initDb(true);

            // save the current admin token as the one to use to get subtitle text
            $app['oauth']->saveAdminToken();
            $app['oauth']->logout();

            return $app['twig']->render('page-success-msg.twig', array(
    
                'msg' => 'Installation Complete.'
            ));

        })->bind('install_complete');

        return $controller;
    }
}
