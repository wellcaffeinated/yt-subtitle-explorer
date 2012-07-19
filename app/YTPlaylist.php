<?php

if (!defined('YTSE_ROOT')) die('Access Denied');

class YTPlaylist {

	static $RefreshInterval = 'PT12H'; // 12 hours

	private $data;
	private $videos;
	private $videoKeys;

	private $ytid;

	private $noData = false;
	
	public function __construct( $id, $app ){
		
		$this->app = $app;
		$this->ytid = $id;
		$this->videos = array();
		$this->videoKeys = array('ytid','title','playlist_id','url','thumbnail','updated','published','position','usid','languages');
		
		$this->sqlSelect = $app['db']->prepare("SELECT * FROM {$app['db.tables.playlists']} WHERE ytid = ?");

		$this->fetchLocal( $id );
	}

	public function getId(){
		return $this->ytid;
	}

	public function hasData(){
		return !$this->noData;
	}

	public function getData(){
		return $this->data;
	}

	public function setData( $arr ){

		foreach ($this->data as $k=>$v){
			if (array_key_exists($k, $arr)){
				$this->data[$k] = $arr[$k];
			}
		}

		if (isset($arr['videos'])){

			foreach ($arr['videos'] as $vid){

				$this->updateVideo($vid);
			}
		}
	}

	// fetch data from local db
	private function fetchLocal( $id ){

		$this->sqlSelect->bindValue(1, $id);
		$this->sqlSelect->execute();
		$this->data = $this->sqlSelect->fetch();

		if ($this->data === false){

			$this->noData = true;

			$this->data = array(
				'ytid'=>$id,
				'last_refresh'=> '1980-01-01T00:00:00+00:00'
			);

			// init
			$this->app['db']->insert($this->app['db.tables.playlists'], $this->data);

		} else {

			foreach ($this->data as $k=>$v){ 
			    if(is_numeric($k))
			    	unset($this->data[$k]);
			}

			$this->videos = unserialize($this->data['video_list']);
		}
	}

	// check to see if we need a refresh from remote
	public function isDirty(){

		if (!$this->data) return true;

		$now = new DateTime('now');
		$timeout = new DateTime($this->data['last_refresh']);
		$timeout->add( new DateInterval(YTPlaylist::$RefreshInterval) );
		return ($now > $timeout);
	}

	public function updateVideo( array $data ){

		if (!isset($data['ytid'])){
			throw Exception('Can not add video. "ytid" must be specified.');
		}

		$this->videos[] = $data['ytid'];
		
		// make sure $data only has valid video data
		$data = array_intersect_key($data, array_flip($this->videoKeys));

		$data['languages'] = serialize($data['languages']);

		// this replace command loses any not-specified data... not the best solution
		$this->app['db']->executeQuery(
			"REPLACE INTO {$this->app['db.tables.videos']} (".implode(',',array_keys($data)).") VALUES (?)",
			array(array_values($data)),
			array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
		);
	}

	public function removeVideo( $ytid ){

		$k = array_search($ytid);

		if ($k){
			array_splice($this->videos, $k, 1);

			// remove playlist association in video record
			$this->app['db']->update(
				$this->app['db.tables.videos'], 
				array('playlist_id' => null),
				array('ytid' => $ytid)
			);
		}
	}

	public function getVideos( array $filter = array() ){

		$vids = $this->app['db']->fetchAll("SELECT * FROM {$this->app['db.tables.videos']} where playlist_id = ?", array($this->ytid));

		foreach ($vids as &$vid){
			$vid['languages'] = unserialize($vid['languages']);
		}

		return $vids;
	}

	public function getVideosWithLang( $lang_code ){

		return $this->getVideos(array('lang_code' => $lang_code));
	}

	public function getVideosWithoutLang( $lang_code ){

		// TODO
	}

	public function syncLocal(){

		$now = new Datetime('now');
		$this->data['last_refresh'] = $now->format('c');
		$this->data['video_list'] = serialize(array_unique($this->videos));
		$this->app['db']->update($this->app['db.tables.playlists'], $this->data, array('ytid'=>$this->data['ytid']));
	}

}
