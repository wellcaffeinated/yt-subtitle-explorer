<?php

namespace YTSE\Captions;

use \Symfony\Component\HttpFoundation\File\UploadedFile;

class CaptionManager {

	private $base;
	
	public function __construct($base){

		$this->base = $base;
	}

	public function getCaptionPath($videoId, $lang_code){

		return implode('/', array(
			$this->base,
			$videoId,
			$lang_code,
		));
	}

	public function saveCaption(UploadedFile $file, $videoId, $lang_code, $username, $format){

		$dir = $this->getCaptionPath($videoId, $lang_code);
		$name = $this->getNewCaptionFilename($username, $format);

		if (!is_dir($dir)){

			mkdir($dir, 0777, true);
		}

		$file->move($dir, $name);
	}

	protected function getNewCaptionFilename($username, $format){

		$ts = time();

		return "{$username}_{$ts}.{$format}";
	}
}
