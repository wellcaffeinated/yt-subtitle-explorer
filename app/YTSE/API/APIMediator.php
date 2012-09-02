<?php

namespace YTSE\API;

use Guzzle\Service\Client;

class APIMediator {

    private $gdataAPI;
    private $ytAPI;
    private $unisubAPI;

    public function __construct(){

        if(!defined('UNISUB_USERNAME') || count(UNISUB_USERNAME) === 0){
            throw new Exception('You MUST specify your Universal Subtitles username in config.php');
        }

        if(!defined('UNISUB_KEY') || count(UNISUB_KEY) === 0){
            throw new Exception('You MUST specify your Universal Subtitles API Key in config.php');
        }

        $this->ytAPI = new Client('http://www.youtube.com/api', array(
            'curl.CURLOPT_SSL_VERIFYHOST' => false,
            'curl.CURLOPT_SSL_VERIFYPEER' => false,
        ));

        $this->gdataAPI = new Client('https://gdata.youtube.com/feeds/api', array(
            'curl.CURLOPT_SSL_VERIFYHOST' => false,
            'curl.CURLOPT_SSL_VERIFYPEER' => false,
        ));

        if(defined('YOUTUBE_KEY') && count(YOUTUBE_KEY) !== 0){
            $this->gdataAPI->setDefaultHeaders(array(
                'X-GData-Key' => 'key='.YOUTUBE_KEY
            ));
        }

        // $this->unisubAPI = new Client('https://www.universalsubtitles.org/api2/partners');
        // $this->unisubAPI->setDefaultHeaders(array(
        //     'X-api-username' => UNISUB_USERNAME,
        //     'X-apikey' => UNISUB_KEY
        // ));

    }

    private function parseXML( $body ){
        
        libxml_use_internal_errors(true);

        if (empty($body) || preg_match('/^\s+$/', $body))
            return null;
        
        $xml = simplexml_load_string($body);
        
        if (!$xml) {

          $err = "Couldn't parse XML response because:\n";
          $xml_errors = libxml_get_errors();
          
          if(!empty($xml_errors)) {

            foreach(libxml_get_errors() as $xml_err)
                $err .= "\n    - " . $xml_err->message;
            
            $err .= "\nThe response was:\n";
            $err .= $body;
            throw new \Exception($err);
          }
        }
        
        return $xml;
    }

    public function getYTLanguages($ytids){

        $ret = array();
        $requests = array();

        if (!is_array($ytids)){

            $ret = $this->getYTLanguages( array($ytids) );
            return $ret[ $ytids ];
        }

        foreach ($ytids as &$id){

            $requests[] = $this->ytAPI->get(
                array('timedtext{?params*}',
                    array(
                        'params' => array(
                            'type' => 'list',
                            'v' => $id,
                        )
                    )
                )
            );
        }

        $responses = $this->ytAPI->send( $requests );

        foreach ($responses as $key => &$r){

            if ($r->isSuccessful()){
                
                $xml = $this->parseXML($r->getBody(true));
                $langs = array();

                foreach($xml->track as $track){
                    $langs[] = array(
                        'lang_code' => (string) $track['lang_code'],
                        'lang_original' => (string) $track['lang_original'],
                        'lang_translated' => (string) $track['lang_translated'],
                        'lang_default' => (string) $track['lang_default']
                    );

                    // place default lang first
                    usort($langs, function($a, $b){

                        if ($a['lang_default'] === 'true'){
                            return -1;
                        }

                        if ($b['lang_default'] === 'true'){
                            return 1;
                        }

                        return 0;
                    });
                }

                $ret[ $ytids[$key] ] = $langs;
            }
        }

        return $ret;
    }

    public function getYTPlaylist($ytid){

        return $this->getPlaylistData($ytid);
    }

    private function getPlaylistData($ytid, $start = 1, &$data = array()){

        try {

            $str = $this->gdataAPI->get(
                array('playlists/{id}{?params*}',
                    array(
                        'id' => $ytid,
                        'params' => array(
                            'v' => '2',
                            'alt' => 'json',
                            'orderby' => 'published',
                            'max-results' => '50', //max allowed
                            'start-index' => $start
                        )
                    )
                )
            )->send()->getBody(true);

        } catch (\Exception $e) {

            return false;
        }

        
        if (!$str){
            return false;
        }

        $json = json_decode($str);

        if (!$json){
            throw new \Exception('Error parsing JSON data for playlist: '.$ytid);
        }

        $data['numVideos'] = (int) $json->feed->{'openSearch$totalResults'}->{'$t'};

        if (empty($json->feed->entry)){
            // no results
            return $data;
        }

        // loop through video results and add to local array
        foreach($json->feed->entry as $k=>$entry){

            $thumb;
            foreach($entry->{'media$group'}->{'media$thumbnail'} as $val){
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

            $data['videos'][] = array(
                'ytid' => $entry->{'media$group'}->{'yt$videoid'}->{'$t'},
                'title' => $entry->title->{'$t'},
                'playlist_id' => $ytid,
                'url' => str_replace('https', 'http', $url),
                'thumbnail' => $thumb,
                'updated'=> $entry->updated->{'$t'},
                'published' => $entry->published->{'$t'},
                'position' => (int) $entry->{'yt$position'}->{'$t'}
            );
        }

        // results are paginated... get next list of results
        if (($start+50) <= $data['numVideos']){

            $this->getPlaylistData($ytid, $start + 50, $data);
        }

        return $data;
    }

}
