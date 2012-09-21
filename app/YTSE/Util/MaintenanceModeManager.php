<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Util;

class MaintenanceModeManager {

	private $filename;

	public function __construct($baseDir){

		$baseDir = preg_replace('/\/$/', '', $baseDir);

		$this->filename = $baseDir . '/' . 'maintenance_mode.txt';
	}

	public function isEnabled(){

		return is_file($this->filename);
	}

	public function enable(){

		touch($this->filename);
	}

	public function disable(){

		if (is_file($this->filename)){

			unlink($this->filename);
		}
	}
}
