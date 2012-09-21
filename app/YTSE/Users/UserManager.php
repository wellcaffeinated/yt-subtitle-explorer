<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\Users;

use Doctrine\DBAL\Connection;

class UserManager {

	private $conn;
	private $tables = array(
		'users' => 'ytse_users',
	);

	/**
	 * Constructor
	 * @param Connection $conn The DBAL connection
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

		$this->conn->query("CREATE TABLE {$this->tables['users']} (
            username TEXT UNIQUE,
            settings BLOB
            )"
        );
	}

	public function getUser($username){

		if (empty($username) || !$username){

			return false;
		}

		$userdata = $this->conn->fetchAssoc("SELECT * FROM {$this->tables['users']} WHERE username = ?", array($username));

		if (!isset($userdata['settings'])){

			return new User($username);	
		}

		return new User($username, unserialize($userdata['settings']));
	}

	public function saveUser(User $user){

		$this->conn->executeQuery(
			"INSERT OR REPLACE INTO {$this->tables['users']} (username, settings) VALUES (?, ?)",
			array(
				$user->getUserName(),
				serialize($user->getUserSettings()),
			)
		);
	}
}
