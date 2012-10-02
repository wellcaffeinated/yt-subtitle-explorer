<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Util;

use Doctrine\DBAL\Connection;

// manages global application states
class StateManager {

    private $conn; // the dbal connection
    private $tables = array(
        'state' => 'ytse_state'
    );

    /**
     * Constructor
     * @param Connection    $conn    The dbal connection
     */
    public function __construct(Connection $conn){

        $this->conn = $conn;
        
        if (!$this->isDbSetup()){

            $this->initDb();
        }
    }
    
    /**
     * Determine if db has proper tables setup
     * @return boolean
     */
    public function isDbSetup(){
        $schema = $this->conn->getSchemaManager();
        return $schema->tablesExist($this->tables['state']);
    }

    /**
     * Setup tables
     * @param  boolean $force If true, drop pre-existing tables
     * @return void
     */
    public function initDb($force = false){

        if ($force){
            $this->conn->query("DROP TABLE IF EXISTS {$this->tables['state']}");
        }

        $this->conn->query("CREATE TABLE {$this->tables['state']} (
            key TEXT UNIQUE,
            value TEXT
            )"
        );
    }

    /**
     * Get a specific state variable
     * @param  string $key key name
     * @return string      value
     */
    public function get($key){

        return $this->conn->fetchColumn("SELECT value FROM {$this->tables['state']} WHERE key = ?", array($key), 0);
    }

    /**
     * Set a specific state variable
     * @param string $key   key name
     * @param string $value value
     * @return  string value provided
     */
    public function set($key, $value){

        $this->conn->executeQuery(
            "REPLACE INTO {$this->tables['state']} (key, value) VALUES (?, ?)",
            array(
                $key,
                $value,
            )
        );

        return $value;
    }
}
