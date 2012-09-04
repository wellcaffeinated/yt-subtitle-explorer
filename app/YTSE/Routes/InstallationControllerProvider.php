<?php

namespace YTSE\Routes;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallationControllerProvider implements ControllerProviderInterface {

    public function connect(Application $app){

        $controller = $app['controllers_factory'];

        $controller->get('/', function() use ($app){

            $app->abort(412, 'Please complete installation by visiting: ' . $app['url_generator']->generate('install'));
        })->bind('search_page');

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

        $controller->get('/install/complete', function() use ($app) {

            if ( !$app['oauth']->isAuthorized() ){
                
                return $app->abort(401, "You are not authorized to complete the installation. Check your admin youtube username setting in the config file.");
            }

            $langFile = YTSE_ROOT.'/app/db/languages.csv';

            $app['db']->query("DROP TABLE IF EXISTS {$app['db.tables.videos']}");
            $app['db']->query("DROP TABLE IF EXISTS {$app['db.tables.playlists']}");
            $app['db']->query("DROP TABLE IF EXISTS {$app['db.tables.languages']}");
            $app['db']->query("DROP TABLE IF EXISTS ".YTSE_DB_ADMIN_TABLE);

            $app['db']->query("CREATE TABLE {$app['db.tables.videos']} (
                ytid TEXT UNIQUE,
                title TEXT,
                playlist_id TEXT,
                url TEXT,
                thumbnail TEXT,
                updated TEXT,
                published TEXT,
                position INTEGER,
                caption_links TEXT,
                languages TEXT
                )"
            );

            $app['db']->query("CREATE TABLE {$app['db.tables.playlists']} (
                ytid TEXT UNIQUE,
                title TEXT,
                updated TEXT,
                video_list TEXT,
                last_refresh TEXT
                )"
            );

            $app['db']->query("CREATE TABLE {$app['db.tables.languages']} (
                lang_code TEXT UNIQUE,
                lang_translated TEXT,
                lang_original TEXT
                )"
            );

            $app['db']->query("CREATE TABLE ".YTSE_DB_ADMIN_TABLE." (
                username TEXT UNIQUE,
                access_token TEXT,
                refresh_token TEXT,
                expires TEXT
                )"
            );

            if (($handle = fopen($langFile, "r")) !== FALSE) {

                $db = $app['db'];

                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {

                    $db->insert($app['db.tables.languages'], array(
                        'lang_code' => $row[0],
                        'lang_translated' => $row[1],
                        'lang_original' => $row[2]
                    ));
                    
                }

                fclose($handle);

            } else {

                throw new \Exception('Can not find language file at '.$langFile);
            }

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
