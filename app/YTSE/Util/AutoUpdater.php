<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Util;

use Guzzle\Service\Client;
use Doctrine\DBAL\Connection;

class AutoUpdater {

    private $client;
    private $meta;
    private $version;

    /**
     * Constructor
     * @param  string $version The current version
     */
    public function __construct($version){

        $this->version = $version;

        $this->client = new Client('http://', array(
            
        ));

        $this->getUpdateMetadata();
    }

    private function getUpdateMetadata(){

        try {

            $json = $this->client->get('update.json')->send()->getBody(true);

        } catch (\Exception $e){

            $this->meta = false;
            return;
        }
        
        $this->meta = json_decode($json, true);
    }

    public function getLatestVersion(){

        if (!$this->meta) return $this->version;

        $latest = $this->meta[0];

        return $latest['version'];
    }
    
    public function needsUpdate(){

        if (!$this->meta){
            return null;
        }

        return (version_compare($this->version, $this->getLatestVersion()) < 0);
    }
    
}
