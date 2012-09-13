<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Playlist;

class YTPlaylist {

	static $RefreshInterval = 'PT12H'; // 12 hours

	private $data;
	private $videos;
	private $videoKeys;

	private $ytid;

	private $noData = false;
	
	/**
	 * Constructor
	 * @param string $id  playlist youtube id
	 * @param Application $app Silex application reference
	 */
	public function __construct( $id, $app ){
		
		//@TODO: this should not depend on $app
		$this->app = $app;
		$this->ytid = $id;
		$this->videos = array();
		$this->videoKeys = array('ytid','title','playlist_id','url','thumbnail','updated','published','position','caption_links','languages');
		
		$this->sqlSelect = $app['db']->prepare("SELECT * FROM {$app['db.tables.playlists']} WHERE ytid = ?");

		$this->fetchLocal( $id );
	}

	/**
	 * Get playlist id
	 * @return string playlist id
	 */
	public function getId(){
		return $this->ytid;
	}

	/**
	 * Does playlist have data
	 * @return boolean
	 */
	public function hasData(){
		return !$this->noData;
	}

	/**
	 * Get the playlist data
	 * @return array playlist data
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * Set the playlist data
	 * @param array $arr The playlist data
	 */
	public function setData( array $arr ){

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

	/**
	 * Fetch data from local db
	 * @param  string $id playlist youtube id
	 * @return void
	 */
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

	/**
	 * Check to see if we need a refresh from remote
	 * @return boolean
	 */
	public function isDirty(){

		if (!$this->data) return true;

		$now = new \DateTime('now');
		$timeout = new \DateTime($this->data['last_refresh']);
		$timeout->add( new \DateInterval(YTPlaylist::$RefreshInterval) );
		return ($now > $timeout);
	}

	/**
	 * Update a particular video's data
	 * @param  array  $data The video data
	 * @return void
	 */
	public function updateVideo( array $data ){

		if (!isset($data['ytid'])){
			throw \Exception('Can not add video. "ytid" must be specified.');
		}

		// don't worry, array_unique is run later
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

	/**
	 * Remove a video from the record
	 * @param  string $ytid the video youtube id
	 * @return void
	 */
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

	/**
	 * Get all video records
	 * @return array
	 */
	private function fetchAllVideos(){
		
		return $this->app['db']->fetchAll("SELECT * FROM {$this->app['db.tables.videos']} WHERE playlist_id = ?", array($this->ytid));
	}

	/**
	 * Get all video data for playlist
	 * @return array video data
	 */
	public function getVideos(){

		$vids = $this->fetchAllVideos();

		foreach ($vids as &$vid){
			$this->filterVidData($vid);
		}

		return $vids;
	}

	/**
	 * Get video data for a specific video
	 * @param  string $id the video youtube id
	 * @return array video data
	 */
	public function getVideoById($id){

		$vid = $this->app['db']->fetchAssoc("SELECT * FROM {$this->app['db.tables.videos']} WHERE ytid = ?", array($id));
		$this->filterVidData($vid);
		return $vid;
	}

	/**
	 * Intermediate stage between database (unserializing etc...)
	 * @param  array $vid video data
	 * @return array video data
	 */
	private function filterVidData(&$vid){

		$cmp = function($a, $b){

			if ($a['lang_code'] === YT_PLAYLIST_DEFAULT_LANG) return -1;
			if ($b['lang_code'] === YT_PLAYLIST_DEFAULT_LANG) return 1;

			return 0;
		};

		$vid['languages'] = $this->getLangData($vid['languages']);
		$vid['caption_links'] = unserialize($vid['caption_links']);

		usort($vid['languages'], $cmp);
		usort($vid['caption_links'], $cmp);
	}

	/**
	 * Get videos filtered by language code
	 * @param  array  $lang_codes The array of language codes to filter
	 * @param  array  $filter     The filter type. $filter['type'] => ('any'|'every') - includes any lang or every lang. $filter['negate'] => (true|false) - Search for languages not present (negative search)
	 * @return array video data
	 */
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

	/**
	 * Sync data to database
	 * @return void
	 */
	public function syncLocal(){

		$now = new \Datetime('now');
		$this->data['last_refresh'] = $now->format('c');
		$this->data['video_list'] = serialize(array_unique($this->videos));
		$this->app['db']->update($this->app['db.tables.playlists'], $this->data, array('ytid'=>$this->data['ytid']));
	}

	/**
	 * Find languages by similar name (for autocomplete)
	 * @param  string $str Language search string
	 * @return array list of languages
	 */
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

	/**
	 * Get string representation of language data for insertion into db
	 * @param  array  $langs language data
	 * @return string
	 */
	private function getLangStr( array $langs ){

		$ret = array();
		foreach($langs as $lang){

			$ret[] = $lang['lang_code'];
		}

		return implode(':', $ret);
	}

	/**
	 * Get array of language data from db string
	 * @param  string $str language code list string
	 * @return array language data for language list
	 */
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

	/**
	 * Store language data to db
	 * @param  array  $data language data
	 * @return void
	 */
	private function storeLangData( array $data ){

		$this->app['db']->executeQuery(
			"INSERT OR IGNORE INTO {$this->app['db.tables.languages']} (lang_code, lang_original, lang_translated) VALUES (?,?,?)",
			array($data['lang_code'],$data['lang_original'],$data['lang_translated'])
		);
	}

}
