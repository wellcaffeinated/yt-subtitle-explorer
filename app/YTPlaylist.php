<?php

if (!defined('YTSE_ROOT')) die('Access Denied');

class YTPlaylist {

	static $RefreshInterval = 'PT12H'; // 12 hours

	private $data;
	
	public function __construct( $id, $app ){
		
		$this->app = $app;

		$this->sqlSelect = $app['db']->prepare("SELECT * FROM {$app['db.tables.playlists']} WHERE ytid = ?");

		$this->fetchLocal( $id );
	}

	public function getData(){
		return $this->data;
	}

	public function setData( $arr ){

		foreach($this->data as $k=>$v){
			if (array_key_exists($k, $arr)){
				$this->data[$k] = $arr[$k];
			}
		}
	}

	// fetch data from local db
	public function fetchLocal( $id ){

		$this->sqlSelect->bindValue(1, $id);
		$this->sqlSelect->execute();
		$this->data = $this->sqlSelect->fetch();

		foreach($this->data as $k=>$v){ 
		    if(is_numeric($k))
		    	unset($this->data[$k]);
		} 

		if ($this->data === false){

			$this->data = array(
				'ytid'=>$id,
				'last_refresh'=> '1980-01-01T00:00:00+00:00'
			);

			// init
			$this->app['db']->insert($this->app['db.tables.playlists'], $this->data);
		}
	}

	// check to see if we need a refresh from remote
	public function isDirty(){

		if (!$this->data) return true;

		$now = new DateTime('now');
		$timeout = new DateTime($this->data['last_refresh']);
		$timeout->add( new DateInterval(YTPlaylist::$RefreshInterval) );
		return $now > $timeout;
	}

	public function getVideos( array $filter = array() ){

		// TODO

	}

	public function getVideosWithLang( $lang_code ){

		return $this->getVideos(array('lang_code' => $lang_code));
	}

	public function getVideosWithoutLang( $lang_code ){

		// TODO
	}

	public function refreshRemote(){

		
	}

	public function syncLocal(){

		$now = new Datetime('now');
		$this->data['last_refresh'] = $now->format('c');
		$this->app['db']->update($this->app['db.tables.playlists'], $this->data, array('ytid'=>$this->data['ytid']));
	}

}
