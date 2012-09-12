<?php

namespace YTSE\API;

use Guzzle\Service\Client;
use Illuminate\Socialite\OAuthTwo\AccessToken;

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
            // 'curl.CURLOPT_SSL_VERIFYHOST' => false,
            // 'curl.CURLOPT_SSL_VERIFYPEER' => false,
        ));

        $this->gdataAPI = new Client('https://gdata.youtube.com/feeds/api', array(
            // 'curl.CURLOPT_SSL_VERIFYHOST' => false,
            // 'curl.CURLOPT_SSL_VERIFYPEER' => false,
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

    public function createYTCaption(AccessToken $token, array $info, $content){

        $params = array(
            'alt' => 'json'
        );
        
        $req = $this->createCaptionRequest('POST', "videos/{$info['videoId']}/captions", $params, $content, $info, $token);
        $ret = $this->execCaptionRequests(array($req));
        return $ret[0];
    }

    public function updateYTCaption($url, AccessToken $token, array $info, $content){

        $params = array(
            'alt' => 'json'
        );

        $req = $this->createCaptionRequest('PUT', $url, $params, $content, $info, $token);
        $ret = $this->execCaptionRequests(array($req));
        return $ret[0];
    }

    public function batchSaveCaptions(array $batch, AccessToken $token){

        $params = array(
            'alt' => 'json'
        );

        foreach ($batch as $cap) {
                
            $url = $cap['url'];

            if ($url){

                $requests[] = $this->createCaptionRequest('PUT', $url, $params, $cap['content'], $cap['info'], $token);

            } else {

                $videoId = $cap['info']['videoId'];
                $requests[] = $this->createCaptionRequest('POST', "videos/$videoId/captions", $params, $cap['content'], $cap['info'], $token);
            }
        }

        return $this->execCaptionRequests( $requests );
    }

    private function createCaptionRequest($method, $url, array $params, $content, array $info, AccessToken $token){

        return $this->gdataAPI->createRequest(
            $method,
            array($url . '{?params*}',
                array(
                    'params' => $params
                )
            ),
            array(
                'Authorization' => 'Bearer ' . $token->getValue(),
                'Content-Type' => 'application/vnd.youtube.timedtext; charset=UTF-8',
                'Content-Language' => $info['lang_code'],
            ),
            $content
        );
    }

    private function execCaptionRequests( array $requests ){

        $errorResponses = array();
        $ret = array();

        try {
            // send a batch
            $responses = $this->gdataAPI->send( $requests );

        } catch (\Guzzle\Common\Exception\ExceptionCollection $e){
            foreach ($e as $exception) {
                if ($exception instanceof \Guzzle\Http\Exception\BadResponseException){
                    $errorResponses[] = $exception->getResponse();
                }
            }
        }

        foreach ( $responses as &$resp ){

            $draft = false;
            $json = json_decode($resp->getBody(true), true);

            $cont = array(
                'caption_data' => array(
                    'lang_code' => $json['entry']['content']['xml$lang'],
                    'src' => $json['entry']['content']['src'],
                ),
                'draft' => $draft,
                'response' => array(
                    'code' => $resp->getStatusCode(),
                    'msg' => $resp->getReasonPhrase(),
                ),
            );

            if (!empty($json['entry']) &&
                !empty($json['entry']['app$control'])
            ){
                
                if ($json['entry']['app$control']['app$draft']['$t'] === 'yes'){

                    $draft = true;
                }

                if ($json['entry']['app$control']['yt$state']['name'] === 'failed'){

                    if ( $json['entry']['app$control']['yt$state']['reasonCode'] === 'invalidFormat' ){

                        $cont[] = array(
                            'type' => 'formatError',
                            'msg' => 'Invalid subtitle format',
                        );

                    } else {

                        $cont[] = array(
                            'type' => 'generalError',
                            'msg' => 'Problem saving caption.',
                        );
                    }
                }
            }

            if (in_array($resp, $errorResponses)){
                $cont['errors'][] = array(
                    'type' => 'requestError',
                    'msg' => 'Problem connecting with youtube API.',
                );
            }

            $ret[] = $cont;
        }

        return $ret;
    }

    public function getYTCaptionContent($url, AccessToken $token, $format = 'srt'){

        $req = $this->gdataAPI->get(
            array($url . '{?params}',
                array(
                    'params' => array(
                        'fmt' => $format === 'srt' ? 'srt' : 'sbv',
                    )
                )
            ),
            array(
                'Authorization' => 'Bearer ' . $token->getValue(),
            )
        );

        return $req->send()->getBody(true);
    }

    public function getYTCaptions($ytids, AccessToken $token){

        $ret = array();
        $requests = array();

        if (!is_array($ytids)){

            $ret = $this->getYTCaptions( array($ytids) );
            return $ret[ $ytids ];
        }

        foreach ($ytids as &$id){

            // queue up requests
            $requests[] = $this->gdataAPI->get(
                array('videos/{video}/captions{?params*}',
                    array(
                        'video' => $id,
                        'params' => array(
                            'alt' => 'json'
                        )
                    )
                ),
                array(
                    'Authorization' => 'Bearer ' . $token->getValue(),
                )
            );
        }

        try {
            // send a batch
            $responses = $this->ytAPI->send( $requests );
        } catch (\Guzzle\Common\Exception\ExceptionCollection $e){
            foreach ($e as $exception) {
                if ($exception instanceof \Guzzle\Http\Exception\BadResponseException
                    && $exception->getResponse()->getStatusCode() !== 403 // means you weren't authorized to view the captions of this video
                ){
                    throw $exception;
                }
            }
        }

        if (!$responses) return $ret;

        // process the responses
        foreach ($responses as $key => &$r){

            if ($r->isSuccessful()){
                
                $json = json_decode($r->getBody(true), true);
                $json = $json['feed'];

                if ($json['openSearch$totalResults']['$t'] > 0){

                    $caps = array();

                    foreach ($json['entry'] as $caption){

                        $caps[] = array(
                            'lang_code' => $caption['content']['xml$lang'],
                            'src' => $caption['content']['src'],
                        );
                    }

                    $ret[ $ytids[$key] ] = $caps;
                }
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
