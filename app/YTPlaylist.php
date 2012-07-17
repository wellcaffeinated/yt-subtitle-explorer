<?php

if (!defined('YTSE_ROOT')) die('Access Denied');

class YTPlaylist {

	static $RefreshInterval = new DateInterval('P12H'); // 12 hours

	public $data;
	private $table = 'playlists'; // table name

	public function __construct( $id, $db ){
		
		$this->db = $db;

		$this->sqlPrepared = $db->prepare("SELECT * FROM {$this->table} WHERE ytid = ?")

		$this->fetchLocal( $id );
	}

	// fetch data from local db
	public fetchLocal( $id ){

		$this->sqlPrepared->bindValue(1, $id);
		$this->sqlPrepared->execute();
		$this->data = $this->sqlPrepared->fetch();
	}

	// check to see if we need a refresh from remote
	public isDirty(){

		if (!$this->data) return true;

		$now = new DateTime('now');
		$timeout = new DateTime($this->data['updated']);
		$timeout->add( YTPlaylist::$RefreshInterval );
		return $now > $timeout;
	}

	public getVideos( array $filter = array() ){

		// TODO

	}

	public getVideosWithLang( $lang_code ){

		return $this->getVideos(array('lang_code' => $lang_code));
	}

	public getVideosWithoutLang( $lang_code ){

		// TODO
	}

	public refreshRemote(){


	}

	public syncLocal(){

	}

}
