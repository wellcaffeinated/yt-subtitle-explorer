<?php

if (!defined('YTSE_ROOT')) die('Access Denied');

require_once YTSE_ROOT.'/includes/Pest.php';
require_once YTSE_ROOT.'/includes/PestXML.php';

class APIMediator {

    private $gdataAPI;
    private $ytAPI;
    private $unisubAPI;

    public function __construct(){

        if(!defined('UNISUB_USERNAME') || empty(UNISUB_USERNAME)){
            throw new Exception('You MUST specify your Universal Subtitles username in config.php');
        }

        if(!defined('UNISUB_KEY') || empty(UNISUB_KEY)){
            throw new Exception('You MUST specify your Universal Subtitles API Key in config.php');
        }

        $this->ytAPI = new PestXML('http://www.youtube.com/api');

        $this->gdataAPI = new Pest('https://gdata.youtube.com/feeds/api');

        if(defined('YOUTUBE_KEY') && !empty(YOUTUBE_KEY)){
            $this->gdataAPI->curl_opts[CURLOPT_HTTPHEADER] = array(
                'X-GData-Key: key='.YOUTUBE_KEY
            );
        }

        $this->unisubAPI = new Pest('https://www.universalsubtitles.org/api2/partners');
        $this->unisubAPI->curl_opts[CURLOPT_HTTPHEADER] = array(
            'X-api-username: '.UNISUB_USERNAME,
            'X-apikey: '.UNISUB_KEY
        );

    }

    public function getYTSubtitles($ytid){

        $ret = array();

        $xml = $ytAPI->get('/timedtext?type=list&v='.$ytid);

        if (!$xml) return $ret;

        foreach($xml->track as $track){

            $ret[] = array(
                'lang_code' => (string) $track['lang_code'],
                'lang_original' => (string) $track['lang_original'],
                'lang_translated' => (string) $track['lang_translated'],
                'lang_default' => (string) $track['lang_default']
            );
        }

        return $ret;
    }

    public getYTPlaylist($ytid){
    }

    private getPlaylistData($ytid, $start = 1, &$data = array())

        $str = $this->gdataAPI->get('/playlists/'.$ytid.'?'.http_build_query(array(
            'v'=>'2',
            'alt'=>'json',
            'orderby'=>'published',
            'max-results'=>'50', //max allowed
            'start-index'=>$start
            )
        );

        
        if (!$str){
            return false;
        }

        $json = json_decode($str);

        if (!$json){
            throw new Exception('Error parsing JSON data for playlist: '.$ytid);
        }

        $data['numVideos'] = (int) $json->feed->{'openSearch$totalResults'}->{'$t'};

        if (empty($json->feed->entry)){
            // no results
            return $data;
        }

        // loop through video results and add to local array
        foreach($json->feed->entry as $k=>$entry){

            $thumb;
            foreach($entry->{'media$thumbnail'} as $val){
                if ($val->{'yt$name'} === 'default'){
                    $thumb = $val->url;
                    break;
                }
            }

            $url;
            foreach($entry->link as $val){
                if ($val->rel === 'alternate'){
                    $url = $val->href;
                    break;
                }
            }

            $data['videos'] = array(
                'ytid' => $entry->{'media$group'}->{'yt$videoid'}->{'$t'},
                'title' => $entry->title->{'$t'},
                'playlist_id' => $ytid,
                'url' => str_replace('https', 'http', $url),
                'thumbnail' => $thumb,
                'updated'=> $entry->updated->{'$t'},
                'position' => (int) $entry->{'yt$position'}->{'$t'}
            );
        }

        // results are paginated... get next list of results
        if (($start+50) <= $$data['numVideos']){

            $this->getPlaylistData($playlist, $start + 50, $data);
        }

        return $data;
    }

}
