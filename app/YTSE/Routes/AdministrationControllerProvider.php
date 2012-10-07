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

class AdministrationControllerProvider implements ControllerProviderInterface {

    public function connect(Application $app){

        $this->app = $app;
        $self = $this;

        $controller = $app['controllers_factory'];

        /**
         * Main admin route
         */
        $controller->match('/', function(Request $req, Application $app) use ($self) {

            $action = $req->get('action');
            $selfUrl = $app['url_generator']->generate('admin_main');
            $isGet = $req->getMethod() === 'GET';
            $error = '';
            $msg = '';

            /**
             * View caption file
             */
            if ($action === 'view'){

                $path = $req->get('path');
                $content = $app['captions']->getCaptionContents($path, 'UTF-8');

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

                if ($isGet){

                    return $app->redirect($selfUrl);
                }
            }

            /**
             * Reject caption file
             */
            if ($action === 'reject'){

                $path = $req->get('path');

                try {

                    $reason = $req->get('reason');

                    if ($reason === 'other'){

                        $reason = $req->get('other_reason');
                    }

                    $info = $app['captions']->extractCaptionInfo($path);
                    $abspath = $app['captions']->getBaseDir() . '/' . $path;
                    $app['captions_rejected']->manageCaptionFile($abspath, $info);

                    $self->sendRejectionEmail(array(
                        'info' => $info,
                        'video' => $app['ytplaylist']->getVideoById($info['videoId']),
                    ), $reason);

                } catch (\Exception $e){

                    $success = false;
                    $error = $e->getMessage();

                }

                if ($isGet){

                    return $app->redirect($selfUrl);
                }
            }

            /**
             * Approve caption
             */
            if (preg_match('/^approve/', $action)){

                $path = $req->get('path')? $req->get('path') : str_replace('approve:', '', $action);
                $content = $app['captions']->getCaptionContents($path, 'UTF-8');

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

                    } else if (!$data['draft']) {

                        $self->sendApprovalEmail(array(
                            'info' => $info,
                            'video' => $video,
                        ));
                    }

                    $info = $app['captions']->extractCaptionInfo($path);
                    $abspath = $app['captions']->getBaseDir() . '/' . $path;
                    $app['captions_approved']->manageCaptionFile($abspath, $info);

                } catch (\Exception $e){

                    $error = $e->getMessage();
                }
                
                if ($isGet){

                    return $app->redirect($selfUrl);
                }
            }

            /**
             * Batch approve
             */
            if ($action === 'batch_approve' && $req->get('selected')) {

                foreach ($req->get('selected') as $path) {

                    $content = $app['captions']->getCaptionContents($path, 'UTF-8');    

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
                        'video' => $video,
                    );
                }

                try {

                    $ret = $app['api']->batchSaveCaptions($batch, $app['oauth']->getValidAdminToken());

                    foreach ($ret as $key => $data) {

                        $item = $batch[$key];
                        $filename = $item['info']['filename'];
                        
                        if (array_key_exists('errors', $data)){

                            $error .= "Problem saving caption: $filename <br/>";

                            foreach ($data['errors'] as $err) {
                                $error .= $err['msg'] . '<br/>';
                            }

                        } else {

                            $self->sendApprovalEmail($item);

                            $path = $item['info']['path'];
                            
                            $info = $app['captions']->extractCaptionInfo($path);
                            $abspath = $app['captions']->getBaseDir() . '/' . $path;
                            $app['captions_approved']->manageCaptionFile($abspath, $info);
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
         * Refresh video data
         */
        $controller->match('/refresh', function(Request $req, Application $app){

            $app['refresh.data']();

            return $app->redirect($app['url_generator']->generate('admin_main'));
        })->bind('admin_refresh_data');

        /**
         * Trash admin route
         */
        $controller->match('/trash', function(Request $req, Application $app) use ($self) {

            $action = $req->get('action');
            $error = '';
            $msg = '';

            if (!$req->get('context')){

                $action = '';

            } else {

                $ctx = $app['captions_' . $req->get('context')];
            }

            /**
             * View caption file
             */
            if ($action === 'view'){

                $path = $req->get('path');
                $content = $ctx->getCaptionContents($path, 'UTF-8');

                if (!$content) $app->abort(404, 'Caption file not found.');

                return new Response($content, 200, array(

                    'Content-type' => 'text/plain',
                ));
            }

            /**
             * Delete caption file
             */
            if (preg_match('/^delete/', $action)){

                $path = $req->get('path')? $req->get('path') : str_replace('delete:', '', $action);

                try {

                    $success = $ctx->deleteCaption($path);

                    if (!$success){

                        $error .= 'Problem deleting caption.';

                    }

                } catch (\Exception $e){

                    $success = false;
                    $error = $e->getMessage();

                }               
            }

            /**
             * Batch delete
             */
            if ($action === 'batch_delete' && $req->get('selected')) {

                foreach ($req->get('selected') as $path) {

                    $success = $ctx->deleteCaption($path);

                    if (!$success){

                        $error .= 'Problem deleting caption: '.$path;

                    }
                }
            }

            /**
             * Retrieve caption
             */
            if (preg_match('/^retrieve/', $action)){

                $path = $req->get('path')? $req->get('path') : str_replace('retrieve:', '', $action);

                try {
                
                    $info = $ctx->extractCaptionInfo($path);
                    $abspath = $ctx->getBaseDir() . '/' . $path;
                    $app['captions']->manageCaptionFile($abspath, $info);

                } catch (\Exception $e){

                    $error = $e->getMessage();
                }
            }

            return $app['twig']->render('page-admin-trash.twig', array(

                'rejected' => $app['captions_rejected']->getSubmissions(),
                'approved' => $app['captions_approved']->getSubmissions(),
                'error' => $error,
                'msg' => $msg,

            ));
        })
        ->method('GET|POST')
        ->bind('admin_trash');

        /**
         * Settings admin route
         */
        $controller->match('/settings', function(Request $req, Application $app) use ($self) {

            $settings = array(
                'ytse.config' => array(
                    'email_notify',
                    'email_from',
                ),
                'swiftmailer.options' => array(
                    'host',
                    'port',
                    'username',
                    'password',
                    'auth_mode',
                ),
                'api.config' => array(
                    'thumbnail',
                ),
                'ytplaylist.config' => array(
                    'playlist',
                ),
            );

            // if submiting a form...
            if ($req->get('continue')){

                $overrides = array();

                foreach ($settings as $key => $vals){

                    $v = $req->get(str_replace('.', '_', $key));

                    if (!$v) continue;

                    foreach ($vals as $setting){

                        if ($v[$setting] !== null){

                            if ("${key}:${setting}" === 'ytse.config:email_notify'){
                                $v[$setting] = explode(',', str_replace(' ', '', $v[$setting]));
                            }

                            $overrides[$key][$setting] = $v[$setting];
                        }
                    }
                }

                // overwrite current settings too
                $self->saveConfig($app, $overrides);
            }

            // get playlist data
            if ($app['session']->get('cache_playlists') === null){

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

                $app['session']->set('cache_playlists', $playlists);
            }

            return $app['twig']->render('page-admin-settings.twig', array(
                'playlists' => $app['session']->get('cache_playlists'),
            ));

        })->method('GET|POST')
        ->bind('admin_settings');

        /**
         * Update route
         */
        $controller->match('/update', function(Request $req, Application $app) use ($self) {

            $action = $req->get('action');
            $error = '';
            $msg = '';

            if ($action === 'update'){

                $app['auto_update']->start();

                return $app->redirect($app['url_generator']->generate('admin_update'));
            }

            return $app['twig']->render('page-admin-update.twig', array(
            ));
        })->method('GET|POST')
        ->bind('admin_update');

        return $controller;
    }

    public function saveConfig(Application $app, array $cfg = array()){

        $config = array(
            'ytse.config' => array(),
            'api.config' => array(),
            'ytplaylist.config' => array(),
            'oauth.config' => array(),
            'captions.config' => array(),
            'swiftmailer.options' => array(),
        );

        foreach ($config as $key => $val) {
            
            $app[$key] = $config[$key] = array_key_exists($key, $cfg) ? 
                    array_merge($app[$key], $cfg[$key]) : 
                    $app[$key];

        }

        $yaml = Yaml::dump($config);

        $yaml = str_replace($app['ytse.root'], '%ytse.root%', $yaml);

        file_put_contents(YTSE_CONFIG_FILE, $yaml);
    }

    public function sendApprovalEmail(array $item){

        $name = $item['info']['user'];
        $user = $this->app['users']->getUser($name);
        $settings = $user->getUserSettings();

        if (!$settings['email_notifications']) return; // don't spam if they don't want it

        $to = $user->getEmail();
        $lang_code = $item['info']['lang_code'];
        $lang = $this->app['ytplaylist']->getLanguageDataByLangCode($lang_code);

        $this->app['email_notification'](
            $to, 
            'Your translation has been approved!', 
            'email-notify-approval.twig',
            array(
                'video' => $item['video'],
                'lang' => $lang,
                'user' => $user,
                'info' => $item['info'],
            )
        );
    }

    public function sendRejectionEmail(array $item, $reason){

        $name = $item['info']['user'];
        $user = $this->app['users']->getUser($name);
        $settings = $user->getUserSettings();

        if (!$settings['email_notifications']) return; // don't spam if they don't want it

        $to = $user->getEmail();
        $lang_code = $item['info']['lang_code'];
        $lang = $this->app['ytplaylist']->getLanguageDataByLangCode($lang_code);

        $this->app['email_notification'](
            $to, 
            'Your translation was rejected', 
            'email-notify-rejection.twig',
            array(
                'video' => $item['video'],
                'lang' => $lang,
                'user' => $user,
                'info' => $item['info'],
                'reason' => $reason,
            )
        );
    }
}
