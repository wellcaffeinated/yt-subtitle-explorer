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
            username TEXT UNIQUE NOT NULL,
            uploads INTEGER NOT NULL DEFAULT 0,
            accepted INTEGER NOT NULL DEFAULT 0,
            rejected INTEGER NOT NULL DEFAULT 0,
            status TEXT,
            settings BLOB
            )"
        );
	}

	public function getUser($username){

		if (empty($username) || !$username){

			return false;
		}

		$userdata = $this->conn->fetchAssoc("SELECT * FROM {$this->tables['users']} WHERE username = ?", array($username));

		if (!$userdata){

			return $this->newUser($username);
		}

		if (!isset($userdata['settings'])){

			return new User($username, array(), $userdata);	
		}

		return new User($username, unserialize($userdata['settings']), $userdata);
	}

	public function getContributors(){

		$ret = array();
		$users = $this->conn->fetchAll("SELECT * FROM {$this->tables['users']} WHERE uploads > 0");

		foreach ($users as $userdata){

			$ret[] = new User($userdata['username'], unserialize($userdata['settings']), $userdata);
		}

		return $ret;
	}

	public function getNumContributors(){

		$count = $this->conn->executeQuery("SELECT COUNT(*) FROM {$this->tables['users']} WHERE uploads > 0")->fetch();
		return $count[0];
	}

	private function newUser($username){

		$ret = $this->conn->executeQuery(
			"INSERT OR REPLACE INTO {$this->tables['users']} (username, settings) VALUES (?, ?)",
			array(
				$username,
				serialize(array()),
			)
		);

		if (!$ret) return null;

		$userdata = $this->conn->fetchAssoc("SELECT * FROM {$this->tables['users']} WHERE username = ?", array($username));

		return new User($username, unserialize($userdata['settings']), $userdata);
	}

	public function saveUser(User $user){

		$this->conn->executeQuery(
			"INSERT OR REPLACE INTO {$this->tables['users']} (username, uploads, accepted, rejected, settings) VALUES (?, ?, ?, ?, ?)",
			array(
				$user->getUserName(),
				$user->get('uploads'),
				$user->get('accepted'),
				$user->get('rejected'),
				serialize($user->getUserSettings()),
			)
		);
	}

	public function incrementUploads($username){

		return $this->incrementValue($username, 'uploads');
	}

	public function incrementAccepted($username){

		return $this->incrementValue($username, 'accepted');
	}

	public function incrementRejected($username){

		return $this->incrementValue($username, 'rejected');
	}

	private function incrementValue($username, $column){

		$ret = $this->conn->executeQuery(
			"UPDATE {$this->tables['users']} SET $column = $column + 1 WHERE username = ?", 
			array($username)
		);

		return !!$ret;
	}
}
