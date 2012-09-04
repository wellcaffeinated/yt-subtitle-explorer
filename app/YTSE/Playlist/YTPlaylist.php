<?php

namespace YTSE\Playlist;

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
		$this->videoKeys = array('ytid','title','playlist_id','url','thumbnail','updated','published','position','caption_links','languages');
		
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

		$now = new \DateTime('now');
		$timeout = new \DateTime($this->data['last_refresh']);
		$timeout->add( new \DateInterval(YTPlaylist::$RefreshInterval) );
		return ($now > $timeout);
	}

	public function updateVideo( array $data ){

		if (!isset($data['ytid'])){
			throw \Exception('Can not add video. "ytid" must be specified.');
		}

		$this->videos[] = $data['ytid'];
		
		// make sure $data only has valid video data
		$data = array_intersect_key($data, array_flip($this->videoKeys));

		if (isset($data['languages'])){

			foreach($data['languages'] as $lang){
				$this->storeLangData( $lang );
			}
			$data['languages'] = $this->getLangStr( $data['languages'] );
		}

		if (isset($data['caption_links'])){

			$data['caption_links'] = serialize($data['caption_links']);
		}

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

	private function fetchAllVideos(){
		
		return $this->app['db']->fetchAll("SELECT * FROM {$this->app['db.tables.videos']} WHERE playlist_id = ?", array($this->ytid));
	}

	public function getVideos(){

		$vids = $this->fetchAllVideos();

		foreach ($vids as &$vid){
			$vid['languages'] = $this->getLangData($vid['languages']);
		}

		return $vids;
	}

	// get videos, filter by lang_codes.
	// $filter['type'] => ('any'|'every') - includes any lang or every lang
	// $filter['negate'] => (true|false) - exclude
	public function getVideosFilterLang( array $lang_codes, array $filter ){

		$type = array_key_exists('type', $filter) && ($filter['type'] === 'every');
		$negate = array_key_exists('negate', $filter) && !$filter['negate'];

		$vids = $this->fetchAllVideos();
		$vids = array_filter($vids, function($v) use ($lang_codes, $type, $negate) {

			$vl = explode(':', $v['languages']);
			foreach ($lang_codes as $lang){
				if($type^$negate^in_array($lang, $vl)){
					return !$type;
				}
			}
			return $type;
		});

		foreach ($vids as &$vid){
			$vid['languages'] = $this->getLangData($vid['languages']);
			$vid['caption_links'] = unserialize($vid['caption_links']);
		}

		return $vids;
	}

	public function getVideoById($id){

		$vid = $this->app['db']->fetchAssoc("SELECT * FROM {$this->app['db.tables.videos']} WHERE ytid = ?", array($id));
		$vid['languages'] = $this->getLangData($vid['languages']);
		$vid['caption_links'] = unserialize($vid['caption_links']);
		return $vid;
	}

	public function syncLocal(){

		$now = new \Datetime('now');
		$this->data['last_refresh'] = $now->format('c');
		$this->data['video_list'] = serialize(array_unique($this->videos));
		$this->app['db']->update($this->app['db.tables.playlists'], $this->data, array('ytid'=>$this->data['ytid']));
	}

	public function getAvailableLanguagesLike( $str = false ){

		if (!$str || strlen($str) === 0){
			
			return $this->app['db']->fetchAll(
				"SELECT * FROM {$this->app['db.tables.languages']}"
			);
		}

		$str .= '%';

		return $this->app['db']->fetchAll(
			"SELECT * FROM {$this->app['db.tables.languages']} WHERE lang_original LIKE ? OR lang_translated LIKE ?",
			array($str, $str)
		);
	}

	private function getLangStr( array $langs ){

		$ret = array();
		foreach($langs as $lang){

			$ret[] = $lang['lang_code'];
		}

		return implode(':', $ret);
	}

	private function getLangData( $str ){

		$ret = array();
		$codes = explode(':', $str);
		$st = $this->app['db']->executeQuery(
			"SELECT * FROM {$this->app['db.tables.languages']} WHERE lang_code in (?)",
			array(array_values($codes)),
			array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
		);

		return $st->fetchAll();
	}

	private function storeLangData( array $data ){

		$this->app['db']->executeQuery(
			"INSERT OR IGNORE INTO {$this->app['db.tables.languages']} (lang_code, lang_original, lang_translated) VALUES (?,?,?)",
			array($data['lang_code'],$data['lang_original'],$data['lang_translated'])
		);
	}

}
