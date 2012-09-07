<?php

namespace YTSE\OAuth;

use Illuminate\Socialite\OAuthTwo\GoogleProvider;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Connection;
use Illuminate\Socialite\UserData;
use Illuminate\Socialite\OAuthTwo\AccessToken;
use Guzzle\Service\Client;

class LoginManager extends GoogleProvider {

	private $session; // the symfony session
	private $conn; // the dbal connection
	private $admin;
	private $ytdataScope = 'https://gdata.youtube.com';
	private $adminToken;

	public function __construct(\Symfony\Component\HttpFoundation\Session\Session $session, Connection $conn, $key, $secret){

		$this->session = $session;
		$this->conn = $conn;
		parent::__construct(new StateStorer($session), $key, $secret);

		$this->scope = $this->getDefaultScope();
	}
	
	public function isAuthorized(){

		$username = $this->session->get('youtube_user');
		$admin = $this->getAdmin();

		return ( $this->isLoggedIn() && $admin !== null && $username === $admin );
	}

	public function setAdmin($name){

		$this->admin = $name;
	}

	public function getAdmin(){

		return $this->admin;
	}

	public function isLoggedIn(){

		return ( null !== $this->session->get('user_data') );
	}

	public function logOut(){

		$this->session->invalidate();
	}

	public function getUserName(){

		return $this->session->get('username');
	}

	public function authenticate(AccessToken $token){

		if ($token->getValue() === null || strlen($token->getValue()) === 0){
			throw \Exception('Invalid Token');
		}

		if ($this->session->get('user_data') === null){

			$user = $this->getUserData( $token );

			if ($user->get('email') && $user->get('verified_email')){		
				$this->session->set('user_data', $user);
				$this->session->set('username', $user->get('email'));
			}
		}

		if ($this->session->get('youtube_auth')){

			$ytdata = $this->getYoutubeData( $token );

			if ($ytdata['entry']['yt$username']['$t']){

				$this->session->set('admin_token', $token);
				$this->session->set('youtube_data', $ytdata);
				$this->session->set('youtube_user', $ytdata['entry']['yt$username']['$t']);
			}
		}
	}

	/**
	 * Get the URL to the provider's auth end-point.
	 *
	 * @param  string  $callbackUrl
	 * @param  array   $options
	 * @return string
	 */
	public function getAuthUrl($callbackUrl, array $options = array()) {

		if ($this->session->get('youtube_auth') && !in_array($this->ytdataScope, $this->getScope())){

			$this->addScope($this->ytdataScope);
			if ( !isset($options['access_type']) ){
				$options['access_type'] = 'offline';
				$options['approval_prompt'] = 'force';
			}
		}
		
		return parent::getAuthUrl($callbackUrl, $options);
	}

	public function getYoutubeData(AccessToken $token){

		$gdata = $this->getHttpClient();

		$userdata = json_decode($gdata->get(array(
		    	'https://gdata.youtube.com/feeds/api/users/default{?params*}',
		    	array(
		    		'params' => array(
		    			'alt' => 'json'
		    		)
		    	)
		    ), 
	    	array(
		        'Authorization' => 'Bearer ' . $token->getValue()
		    )
	    )->send()->getBody(true), true);

	    return $userdata;
	}

	private function getAdminToken(){

		if ($this->adminToken !== null) return $this->adminToken;

		$data = $this->conn->fetchAssoc("SELECT access_token, refresh_token, expires FROM ".YTSE_DB_ADMIN_TABLE." WHERE username = ?", array($this->getAdmin()));

		if ($data){

			$this->adminToken = new AccessToken();
			$this->adminToken->replace($data);
		}

		return $this->adminToken;
	}

	public function getValidAdminToken(){

		$token = $this->getAdminToken();

        if (!$token) return false;

        if ($this->isAdminTokenExpired()){
        	$token = $this->refreshAdminToken();
        }

        return $token;
	}

	public function adminTokenAvailable(){

		if ($this->getAdminToken() !== false){

			return true;
		}

		return false;
	}

	public function saveAdminToken(){

		if (!$this->isAuthorized()) return;

		$token = $this->session->get('admin_token');

		if (!$token) return;

		$expires = new \Datetime('now');
		$expires->add( \DateInterval::createFromDateString($token->get('expires_in') . ' seconds') );

		$this->conn->executeQuery(
			"INSERT OR REPLACE INTO ".YTSE_DB_ADMIN_TABLE." (username, access_token, refresh_token, expires) VALUES (?,?,?,?)",
			array(
				$this->session->get('youtube_user'),
				$token->getValue(),
				$token->get('refresh_token'),
				$expires->format('c'),
			)
		);
	}

	private function isAdminTokenExpired(){

		$token = $this->getAdminToken();

		if (!$token) throw \Exception('Admin token unavailable');

		$now = new \DateTime('now');
		$expires = new \DateTime($token->get('expires'));

		return ($now > $expires);
	}

	private function refreshAdminToken(){

		$token = $this->getAdminToken();

		$client = $this->getHttpClient();

		$query['client_id'] = $this->getClientId();
		$query['client_secret'] = $this->getClientSecret();
		$query['grant_type'] = 'refresh_token';
		$query['refresh_token'] = $token->get('refresh_token');

		$response = $this->executeAccessRequest($client, $query);

		$parameters = $this->parseAccessResponse($response);

		$token = $this->createAccessToken($parameters);

		$expires = new \Datetime('now');
		$expires->add( \DateInterval::createFromDateString($token->get('expires_in') . ' seconds') );

		$this->conn->update(
			YTSE_DB_ADMIN_TABLE, 
			array(
				'access_token' => $token->getValue(),
				'expires' => $expires->format('c'),
			),
			array('username' => $this->getAdmin())
		);

		return $this->adminToken = $token;
	}

	public function hasYoutubeAuth(){

		return (null !== $this->session->get('youtube_data'));
	}

	public function doYoutubeAuth(){

		$this->session->set('youtube_auth', true);
	}
}
