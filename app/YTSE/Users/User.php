<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Users;

class User {

	private $username;
	private $settings = array(
		'email_notifications' => true,
	);

	public function __construct($username, array $settings = array()){

		$this->username = $username;
		$this->setUserSettings($settings);
	}

	public function getUserName(){

		return $this->username;
	}

	public function getUserSettings(){

		return $this->settings;
	}

	public function setUserSettings(array $settings){

		foreach ($this->settings as $k => $v){
			if (array_key_exists($k, $settings)){
				$this->settings[$k] = $settings[$k];
			}
		}
	}
}
