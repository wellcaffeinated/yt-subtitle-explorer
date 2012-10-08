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

    public static $metaDataURL = 'https://raw.github.com/wellcaffeinated/yt-subtitle-explorer/feature-auto-update/_dist/update.json';
    private $client;
    private $meta;
    private $version;
    private $lockfile;

    /**
     * Constructor
     * @param  string $version The current version
     * @param  string $lockfileName A clean path to create/retrieve a lockfile
     */
    public function __construct($version, $lockfileName){

        $this->version = $version;
        $this->lockfile = $lockfileName;

        $this->client = new Client(AutoUpdater::$metaDataURL, array(
            
        ));

        $this->getUpdateMetadata();
    }

    private function getUpdateMetadata(){

        try {

            $json = $this->client->get()->send()->getBody(true);

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

    private function hasLockfile(){

        return is_file($this->lockfile);
    }

    private function addLockfile(){

        touch($this->lockfile);
    }

    private function removeLockfile(){

        unlink($this->lockfile);
    }

    public function start(\Silex\Application $app){

        if (!$this->needsUpdate()) return;
        if ($this->hasLockfile()) throw new \Exception('Lockfile found. Update process has already been started.');

        $this->addLockfile();

        try {

            // iterate up through versions
            $i = count($this->meta);
            while ($i-- >= 0){

                // if current version is larger... keep going...
                if (version_compare($this->version, $this->meta[$i]['version']) >= 0) continue;

                $this->doUpdate($app, $this->meta[$i]);
            }
            
        } catch (\Exception $e) {
        
            $this->removeLockfile();
            throw $e;
        }

        $this->removeLockfile();
    }
    
    private function doUpdate(\Silex\Application $app, $meta){

        $basedir = sys_get_temp_dir();

        // get package
        $remotePackage = $meta['package'];
        $ext = pathinfo($remotePackage, PATHINFO_EXTENSION);
        $package = tempnam($basedir, $ext);
        copy($remotePackage, $package);

        // extract package
        $packagedir = $basedir . '/' . $meta['version'];
        $zip = new \ZipArchive;
        $res = $zip->open($package);
        if ($res === true) {
            
            $zip->extractTo($packagedir);
            $zip->close();

        } else {

            throw new \Exception('Failed to open zip package at: '.$package);
        }

        // run update script
        $script = $packagedir . '/update.php';
        if (is_file($script)){

            $fn = include $script;

            if (is_callable($fn)){

                $fn($app);
            }
        }

        $this->version = $meta['version'];
    }
}
