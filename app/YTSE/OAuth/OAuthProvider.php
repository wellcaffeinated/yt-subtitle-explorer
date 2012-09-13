<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\OAuth;

use Silex\Application;
use Silex\ServiceProviderInterface;

class OAuthProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['oauth'] = $app->share(function($app){

            $config = $app['oauth.config'];

            if ( $config['provider'] === 'google' ){

                if (!array_key_exists('key', $config) || !array_key_exists('secret', $config) || !array_key_exists('admin', $config))
                    throw new \Exception('Please define oauth.config credentials: "key", "secret", and "admin"');

                $manager = new LoginManager($app['session'], $app['db'], $config['key'], $config['secret']);
                $manager->setAdmin($config['admin']);
                return $manager;
            }
            
            throw new \Exception('Unsupported OAuth Provider.');
        });
    }

    public function boot(Application $app){
    }
}
