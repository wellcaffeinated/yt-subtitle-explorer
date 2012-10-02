<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Util;

use Silex\Application;
use Silex\ServiceProviderInterface;

class EmailNotificationProvider implements ServiceProviderInterface {
    
    public function register(Application $app){

        $app['email_notification'] = $app->protect(function($to, $subject, $tpl, array $params = array()) use ($app) {

			$config = $app['ytse.config'];

	        $msg = $app['twig']->render($tpl, $params);

	        $app['monolog']->addInfo( 'emailing: ' . 
	            (is_array($to) ? implode(',', $to) : $to) . 
	            ' from: ' . 
	            (is_array($config['email_from']) ? implode(',', $config['email_from']) : $config['email_from'])
	        );

	        $email = \Swift_Message::newInstance()
	            ->setSubject($subject)
	            ->setFrom($config['email_from'])
	            ->setTo($to)
	            ->setBody($msg);

	        $count = $app['mailer']->send($email);
	        
	        $app['monolog']->addInfo( 'emailed ' . $count . ' recipients');

	        return $count;
		});

    }

    public function boot(Application $app){
    }
}
