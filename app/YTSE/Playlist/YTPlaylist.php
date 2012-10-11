<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Playlist;

use Doctrine\DBAL\Connection;

class YTPlaylist {

    static $RefreshInterval = 'PT12H'; // 12 hours

    private $data;
    private $videos;
    private $videoKeys;

    private $ytid;

    private $noData = false;
    private $language_file_path;
    private $default_lang;

    private $tables = array(
        'playlists' => 'ytse_playlists',
        'videos' => 'ytse_videos',
        'languages' => 'ytse_languages',
    );
    
    /**
     * Constructor
     * @param string $id  playlist youtube id
     * @param Connection $conn DBAL connection
     */
    public function __construct( $id, Connection $conn, $language_file_path, $default_lang = 'en' ){
        
        $this->conn = $conn;
        $this->ytid = $id;
        $this->videos = array();
        $this->language_file_path = $language_file_path;
        $this->default_lang = $default_lang;
        $this->videoKeys = array('ytid','title','playlist_id','url','thumbnail','updated','published','position','caption_links','languages');
        
        if($this->isDbSetup()){

            $this->sqlSelect = $this->conn->prepare("SELECT * FROM {$this->tables['playlists']} WHERE ytid = ?");
            $this->fetchLocal( $id );
        }
    }

    /**
     * Determine if db has proper tables setup
     * @return boolean
     */
    public function isDbSetup(){
        
        $schema = $this->conn->getSchemaManager();

        foreach ($this->tables as $table){

            if ( !$schema->tablesExist($table) )
                return false;
        }

        return true;
    }

    /**
     * Setup tables
     * @param  boolean $force If true, drop pre-existing tables
     * @return void
     */
    public function initDb($force = false){

        if ($force){
            foreach ($this->tables as $table) {
                $this->conn->query("DROP TABLE IF EXISTS {$table}");
            }
        }

        $this->conn->query("CREATE TABLE {$this->tables['videos']} (
            ytid TEXT UNIQUE,
            title TEXT,
            playlist_id TEXT,
            url TEXT,
            thumbnail TEXT,
            updated TEXT,
            published TEXT,
            position INTEGER,
            caption_links TEXT,
            languages TEXT
            )"
        );

        $this->conn->query("CREATE TABLE {$this->tables['playlists']} (
            ytid TEXT UNIQUE,
            title TEXT,
            updated TEXT,
            video_list TEXT,
            last_refresh TEXT
            )"
        );

        $this->conn->query("CREATE TABLE {$this->tables['languages']} (
            lang_code TEXT UNIQUE,
            lang_translated TEXT,
            lang_original TEXT
            )"
        );

        if (($handle = fopen($this->language_file_path, "r")) !== FALSE) {

            $langs = $this->tables['languages'];

            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {

                $this->conn->insert($langs, array(
                    'lang_code' => $row[0],
                    'lang_translated' => $row[1],
                    'lang_original' => $row[2]
                ));
            }

            fclose($handle);

        } else {

            throw new \Exception('Can not find language file at '.$this->language_file_path);
        }

        $this->sqlSelect = $this->conn->prepare("SELECT * FROM {$this->tables['playlists']} WHERE ytid = ?");
        $this->fetchLocal( $this->ytid );
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
            $this->conn->insert($this->tables['playlists'], $this->data);

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
        $this->conn->executeQuery(
            "REPLACE INTO {$this->tables['videos']} (".implode(',',array_keys($data)).") VALUES (?)",
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
            $this->conn->update(
                $this->tables['videos'], 
                array('playlist_id' => null),
                array('ytid' => $ytid)
            );
        }
    }

    /**
     * Get all video records
     * @return array
     */
    private function fetchAllVideos($orderby = 'position', $asc = false){
    
        $dir = $asc ? 'ASC' : 'DESC';
        
        return $this->conn->fetchAll("SELECT * FROM {$this->tables['videos']} WHERE playlist_id = ? ORDER BY ? $dir", array($this->ytid, $orderby));
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

        $vid = $this->conn->fetchAssoc("SELECT * FROM {$this->tables['videos']} WHERE ytid = ?", array($id));
        $this->filterVidData($vid);
        return $vid;
    }

    /**
     * Intermediate stage between database (unserializing etc...)
     * @param  array $vid video data
     * @return array video data
     */
    private function filterVidData(&$vid){

        $default_lang = $this->default_lang;

        $cmp = function($a, $b) use ($default_lang) {

            if ($a['lang_code'] === $default_lang) return -1;
            if ($b['lang_code'] === $default_lang) return 1;

            return 0;
        };

        $vid['languages'] = $this->getLangData($vid['languages']);
        $vid['caption_links'] = unserialize($vid['caption_links']);

        if ($vid['languages']){
            usort($vid['languages'], $cmp);
        }

        if ($vid['caption_links']){
            usort($vid['caption_links'], $cmp);
        }
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
        $this->conn->update($this->tables['playlists'], $this->data, array('ytid'=>$this->data['ytid']));
    }

    /**
     * Find languages by similar name (for autocomplete)
     * @param  string $str Language search string
     * @return array list of languages
     */
    public function getAvailableLanguagesLike( $str = false ){

        if (!$str || strlen($str) === 0){
            
            return $this->conn->fetchAll(
                "SELECT * FROM {$this->tables['languages']}"
            );
        }

        $str .= '%';

        return $this->conn->fetchAll(
            "SELECT * FROM {$this->tables['languages']} WHERE lang_original LIKE ? OR lang_translated LIKE ? OR lang_original LIKE ? OR lang_translated LIKE ?",
            array($str, $str, '% '.$str, '% '.$str)
        );
    }

    /**
     * Get language data for one language code
     * @param  string $lang_code The language code
     * @return array            language data
     */
    public function getLanguageDataByLangCode( $lang_code ){

        return $this->conn->fetchAssoc(
            "SELECT * FROM {$this->tables['languages']} WHERE lang_code = ?",
            array($lang_code)
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
        $st = $this->conn->executeQuery(
            "SELECT * FROM {$this->tables['languages']} WHERE lang_code in (?)",
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

        $this->conn->executeQuery(
            "INSERT OR IGNORE INTO {$this->tables['languages']} (lang_code, lang_original, lang_translated) VALUES (?,?,?)",
            array($data['lang_code'],$data['lang_original'],$data['lang_translated'])
        );
    }

}
