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

    public static $metaDataURL = 'https://raw.github.com/wellcaffeinated/yt-subtitle-explorer/master/_dist/update.json';
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

        $this->fetchUpdateMetadata();
    }

    /**
     * Fetch metadata from server
     * @return void
     */
    private function fetchUpdateMetadata(){

        try {

            $json = $this->client->get()->send()->getBody(true);

        } catch (\Exception $e){

            $this->meta = false;
            return;
        }
        
        $this->meta = json_decode($json, true);
    }

    /**
     * Get latest version
     * @return string version string for latest
     */
    public function getLatestVersion(){

        if (!$this->meta) return $this->version;

        $latest = $this->meta[0];

        return $latest['version'];
    }
    
    /**
     * Answer question. Do we need to update?
     * @return boolean
     */
    public function needsUpdate(){

        if (!$this->meta){
            return null;
        }

        return (version_compare($this->version, $this->getLatestVersion()) < 0);
    }

    /**
     * Is there a lockfile already?
     * @return boolean
     */
    private function hasLockfile(){

        return is_file($this->lockfile);
    }

    /**
     * Create a lockfile
     */
    private function addLockfile(){

        touch($this->lockfile);
    }

    /**
     * remove the lockfile
     * @return void
     */
    private function removeLockfile(){

        unlink($this->lockfile);
    }

    /**
     * Start the update process
     * @param  \Silex\Application $app The main silex application
     * @return void
     */
    public function start(\Silex\Application $app){

        if (!$this->needsUpdate()) return;
        if ($this->hasLockfile()) throw new LockfileException('Lockfile found. Update process has already been started.');

        $this->addLockfile();
        set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        });

        try {

            // iterate up through versions
            $i = count($this->meta);
            $continue;
            while ($i > 0 && (!isset($continue) || $continue !== false)){

                $i--;

                // if current version is larger... keep going...
                if (version_compare($this->version, $this->meta[$i]['version']) >= 0) continue;

                $app['monolog']->addDebug('Updater: Beginning update to version '.$this->meta[$i]['version']);

                // if continue is false, it's not an error... it just means we want to refresh the script before proceeding
                $continue = $this->doUpdate($app, $this->meta[$i]);
            }
            
        } catch (\Exception $e) {
        
            $this->removeLockfile();
            throw $e;
        }

        restore_error_handler();
        $this->removeLockfile();
    }
    
    /**
     * Do a single update to version with specified metadata
     * @param  \Silex\Application $app  Silex application
     * @param  array             $meta  metadata
     * @return boolean                  the return value of the update script.
     */
    private function doUpdate(\Silex\Application $app, $meta){

        $ret = true;
        $basedir = sys_get_temp_dir();

        // get package
        $remotePackage = $meta['package'];
        $ext = pathinfo($remotePackage, PATHINFO_EXTENSION);
        $package = tempnam($basedir, $ext);

        // test for 404.. throw error of can't find it
        $this->client->head($remotePackage)->send()->getStatusCode();

        $success = copy($remotePackage, $package);

        if (!$success){

            throw new \Exception("Failed to download update package");
        }

        $app['monolog']->addDebug('Updater: downloaded package to '.$package);

        // extract package
        $packagedir = $basedir . '/' . $meta['version'];
        $zip = new \ZipArchive;
        $res = $zip->open($package);
        if ($res === true) {
            
            $zip->extractTo($packagedir);
            $zip->close();

            $app['monolog']->addDebug('Updater: extracted package to '.$packagedir);

        } else {

            throw new \Exception('Failed to open zip package at: '.$package);
        }

        // run update script
        $script = $packagedir . '/update.php';
        if (is_file($script)){

            $fn = include $script;

            if (is_callable($fn)){

                $ret = $fn($app);

                $app['monolog']->addDebug('Updater: ran update script in '.$script);
            }
        }

        $this->version = $meta['version'];

        return $ret;
    }
}
