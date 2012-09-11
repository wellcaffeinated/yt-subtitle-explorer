<?php

namespace YTSE\Captions;

use \Symfony\Component\HttpFoundation\File\UploadedFile;

class CaptionManager {

	private $base;
	private static $acceptedExts = 'txt sub srt sbv';
	private static $maxAcceptedSize = 1048576; // 1Mb
	
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

	public function getCaptionContents($path){

		$filename = $this->base . '/' . $path;

		if (!is_file($filename)) return false;

		try{

			return file_get_contents($filename);

		} catch (\Exception $e) {}

		return false;
	}

	public function deleteCaption($path){

		$filename = $this->base . '/' . $path;

		if (!is_file($filename)) return false;

		unlink($filename);

		$dir = dirname($filename);
		@rmdir($dir); // remove if empty

		return true;
	}

	public function getSubmissions(){

		return $this->generateIndex();
	}

	protected function generateIndex(){

		$ret = array();

		if (!is_dir($this->base)) {

			return $ret;
		}

		$d = dir($this->base);

		while (false !== ($entry = $d->read())) {

			if (preg_match('/^\./', $entry)) continue; // begins with .

			$vid = array(
				'videoId' => $entry,
				'captions' => $this->generateCaptionsForVideo($entry),
			);

			if (!empty($vid['captions'])){

				$ret[] = $vid;
			}
		}

		$d->close();

		return $ret;
	}

	protected function generateCaptionsForVideo($videoId){

		$dirname = $this->base . '/' . $videoId;
		$langs = array();

		if (!is_dir($dirname)) {
			return $langs;
		}

		$d = dir($dirname);

		while (false !== ($lang_code = $d->read())) {

			if (preg_match('/^\./', $lang_code)) continue; // begins with .

			$caps = $this->generateCaptionsForVideoAndLang($videoId, $lang_code);

			if (!empty($caps)){

				$langs[ $lang_code ] = $caps;
			}
		}

		$d->close();

		return $langs;
	}

	public function extractCaptionInfo($path){

		$path = preg_replace('/^\//', '', $path); // remove leading slash
		$dirs = explode('/', $path);

		if (count($dirs) !== 3) return false;

		$filename = $dirs[2];
		$count = preg_match('/^([^%]+)%([0-9]+)\.(\w+)$/', $filename, $matches);

		if (!$count) return false;

		return array(
			'videoId' => $dirs[0],
			'lang_code' => $dirs[1],
			'path' => $path,
			'filename' => $filename,
			'user' => $matches[1],
			'timestamp' => $matches[2],
			'ext' => $matches[3],
		);
	}

	protected function generateCaptionsForVideoAndLang($videoId, $lang_code){

		$dirname = $this->base . '/' . $videoId . '/' . $lang_code;
		$caps = array();

		if (!is_dir($dirname)) {
			return $caps;
		}

		$d = dir($dirname);

		while (false !== ($entry = $d->read())) {

			if (preg_match('/^\./', $entry)) continue; // begins with .

			$info = $this->extractCaptionInfo(str_replace($this->base, '', $dirname) . '/' . $entry);

			if (!$info) continue;

			$caps[] = $info;
		}

		$d->close();

		return $caps;
	}

	public function saveCaption(UploadedFile $file, $videoId, $lang_code, $username){

		preg_match('/\.([a-zA-Z]*)$/', $file->getClientOriginalName(), $matches);
		$format = isset($matches[1])? $matches[1] : 'txt';
		
		$dir = $this->getCaptionPath($videoId, $lang_code);
		$name = $this->getNewCaptionFilename($username, $format);

		if ( !$this->isSafeExtension($format) ){

			// if someone tries to upload a .php file, for example... stop them.
			throw new InvalidFileFormatException("Invalid File Format");
			return;
		}

		if ( $file->getSize() > CaptionManager::$maxAcceptedSize ){

			throw new \Exception("File too big.");
			return;
		}

		if (!is_dir($dir)){

			mkdir($dir, 0777, true);
		}

		$file->move($dir, $name);
	}

	protected function isSafeExtension($format){

		return in_array($format, explode(' ', CaptionManager::$acceptedExts));
	}

	protected function getNewCaptionFilename($username, $format){

		$ts = time();

		return "{$username}%{$ts}.{$format}";
	}
}
