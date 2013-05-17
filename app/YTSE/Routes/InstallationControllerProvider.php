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
use Symfony\Component\Yaml\Yaml;
use Guzzle\Service\Client;

class InstallationControllerProvider implements ControllerProviderInterface {

    public function saveConfig(Application $app, array $cfg){

        $config = array(
            'ytse.config' => array(),
            'api.config' => array(),
            'ytplaylist.config' => array(),
            'oauth.config' => array(),
            'captions.config' => array(),
            'swiftmailer.options' => array(),
        );

        foreach ($config as $key => $val) {
            
            $config[$key] = array_key_exists($key, $cfg) ? 
                    array_merge($app[$key], $cfg[$key]) : 
                    $app[$key];

        }

        $yaml = Yaml::dump($config);

        $yaml = str_replace($app['ytse.root'], '%ytse.root%', $yaml);

        file_put_contents(YTSE_CONFIG_FILE, $yaml);
    }

    public function connect(Application $app){

        $self = $this;

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

            return $app['twig']->render('page-install.twig');

        })->bind('install');

        $controller->post('/install', function(Request $req) use ($app, $self){

            $self->saveConfig($app, array(
                'oauth.config' => array(
                    'key' => $req->get('gkey'),
                    'secret' => $req->get('gsecret'),
                ),
                'api.config' => array(
                    'yt.api.key' => $req->get('ytkey'),
                ),
            ));

            return $app->redirect($app['url_generator']->generate('install_authenticate'));

        })->bind('install_post');

        /**
         * Do authentication
         */
        $controller->get('/install/authenticate', function(Request $req) use ($app){

            if ( !$app['oauth']->isLoggedIn() ){
                
                $app['oauth']->doYoutubeAuth();
                $app['session']->set('login_referrer', $req->getRequestUri());
                $app['session']->set('installing', 'yes');

                return $app['twig']->render('page-info-msg.twig', array(
    
                    'msg' => 'You need to login first. Click "Ok" to begin logging in.',
                    'ok_action' => $app['url_generator']->generate('authenticate'),
                ));

            }

            $app['oauth']->setAdmin($app['oauth']->getYTUserName());

            if ( !$app['oauth']->isAdminTokenValid() ){

                $app['oauth']->logOut();
                $app['monolog']->addError('Install: Invalid admin token.');

                return $app['twig']->render('page-error-msg.twig', array(
                    'ok_action' => $app['url_generator']->generate('install_authenticate'),
                    'msg' => 'Something went wrong with authentication. Please try authenticating again.',
                ));
            }

            return $app->redirect($app['url_generator']->generate('install_config'));

        })->bind('install_authenticate');

        $controller->match('/install/config', function(Request $req) use ($app, $self){

            if ($req->get('continue')){

                $self->saveConfig($app, array(
                    'oauth.config' => array(
                        'admin' => $app['oauth']->getYTUserName(),
                    ),
                    'ytplaylist.config' => array(
                        'playlist' => $req->get('playlist'),
                        'default_lang' => $req->get('default_lang'),
                    ),
                ));          

                return $app->redirect($app['url_generator']->generate('install_complete'));                
            }

            $client = new Client('https://gdata.youtube.com/feeds/api');
            $client->setDefaultHeaders(array(
                'X-GData-Key' => 'key='.$app['api.config']['yt.api.key'],
            ));

            $resp = $client->get(array(
                'users/{user}/playlists{?params*}',
                array(
                    'user' => $app['oauth']->getYTUserName(),
                    'params' => array(
                        'alt' => 'json',
                        'v' => '2',
                    )
                )
            ))->send()->getBody(true);

            $json = json_decode($resp, true);

            foreach ($json['feed']['entry'] as $pl) {
                $playlists[] = array(
                    'name' => $pl['title']['$t'],
                    'id' => $pl['yt$playlistId']['$t'],
                );
            }

            return $app['twig']->render('page-install-config.twig', array(
                'playlists' => $playlists,
            ));
        })
        ->bind('install_config')
        ->method('GET|POST');

        /**
         * Actually do install
         */
        $controller->get('/install/complete', function() use ($app) {

            if ( !$app['oauth']->isAuthorized() ){
                
                return $app->abort(401, "You are not authorized to complete the installation. Check your admin youtube username setting in the config file.");
            }

            $app['oauth']->logout();
            $app['state']->set('ytse_installed', 'yes');

            $app['refresh.data']();

            return $app['twig']->render('page-success-msg.twig', array(
    
                'msg' => 'Installation Complete.'
            ));

        })->bind('install_complete');

        return $controller;
    }
}
