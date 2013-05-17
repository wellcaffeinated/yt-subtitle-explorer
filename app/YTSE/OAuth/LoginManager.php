<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\OAuth;

use Illuminate\Socialite\OAuthTwo\GoogleProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\DBAL\Connection;
use Illuminate\Socialite\UserData;
use Illuminate\Socialite\OAuthTwo\AccessToken;
use Guzzle\Service\Client;
use YTSE\Users\UserManager;

// manages google oauth
class LoginManager extends GoogleProvider {

    private $session; // the symfony session
    private $conn; // the dbal connection
    private $admin;
    private $userManager;
    private $ytdataScope = 'https://gdata.youtube.com';
    private $adminToken;
    private $tables = array(
        'admin' => 'ytse_admin'
    );

    /**
     * Constructor
     * @param Session       $session The symfony session
     * @param Connection    $conn    The dbal connection
     * @param UserManager   $um      The YTSE user manager
     * @param string        $key     The oauth key
     * @param string        $secret  The oauth secret
     */
    public function __construct(Session $session, Connection $conn, UserManager $um, $key, $secret){

        $this->session = $session;
        $this->conn = $conn;
        $this->userManager = $um;
        parent::__construct(new StateStorer($session), $key, $secret);

        $this->scope = $this->getDefaultScope();

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
        return $schema->tablesExist($this->tables['admin']);
    }

    /**
     * Setup tables
     * @param  boolean $force If true, drop pre-existing tables
     * @return void
     */
    public function initDb($force = false){

        if ($force){
            $this->conn->query("DROP TABLE IF EXISTS {$this->tables['admin']}");
        }

        $this->conn->query("CREATE TABLE {$this->tables['admin']} (
            username TEXT UNIQUE NOT NULL,
            access_token TEXT,
            refresh_token TEXT,
            expires TEXT,
            revoked NUMERIC
            )"
        );
    }

    /**
     * Is user the admin?
     * @return boolean
     */
    public function isAuthorized(){

        $username = $this->getYTUserName();
        $admin = $this->getAdmin();

        return ( $this->isLoggedIn() && $admin !== null && $username === $admin );
    }

    /**
     * Set the admin username
     * @param string $name admin username
     */
    public function setAdmin($name){

        $this->admin = $name;
    }

    /**
     * Get admin username
     * @return string admin username
     */
    public function getAdmin(){

        return $this->admin;
    }

    /**
     * Is the user authenticated with google
     * @return boolean
     */
    public function isLoggedIn(){

        return ( null !== $this->session->get('user_object') );
    }

    /**
     * Log the user out (invalidate current session)
     * @return void
     */
    public function logOut(){

        $this->session->invalidate();
    }

    /**
     * Get current username
     * @return string current user's username
     */
    public function getUserName(){

        $user = $this->session->get('user_object');

        if (!$user) return null;

        return $user->getUserName();
    }

    public function getYTUserName(){

        $user = $this->session->get('user_object');

        if (!$user) return null;

        return $user->get('ytusername');
    }

    /**
     * Complete authentication based on access token
     * @param  AccessToken $token The access token object
     * @return void
     */
    public function authenticate(AccessToken $token, $install = false){

        if ($token->getValue() === null || strlen($token->getValue()) === 0){
            throw \Exception('Invalid Token');
        }

        if ($this->session->get('user_object') === null){

            $data = $this->getUserData( $token );

            if ($data->get('email') && $data->get('verified_email')){

                $user = $this->userManager->getUser($data->get('email'));
                $this->session->set('user_object', $user);
            }
        }

        if ($this->session->get('youtube_auth')){

            $this->session->set('youtube_auth', false);

            $ytdata = $this->getYoutubeData( $token );

            if ($ytdata['entry']['yt$username']['$t']){

                $user->set('ytusername', $ytdata['entry']['yt$username']['$t']);
                $this->userManager->saveUser($user);
                
                if ($install){

                    $this->setAdmin( $this->getYTUserName() );
                }

                $this->saveAdminToken($token);
            }
        }
    }

    /**
     * Get the URL to the provider's auth end-point.
     *
     * @param  string  $callbackUrl Callback url for oauth process
     * @param  array   $options http request params
     * @return string
     */
    public function getAuthUrl($callbackUrl, array $options = array()) {

        if ($this->session->get('youtube_auth')){

            if ( !in_array($this->ytdataScope, $this->getScope()) ){
                $this->addScope($this->ytdataScope);
            }
            
            if ( !isset($options['access_type']) ){
                $options['access_type'] = 'offline';
                $options['approval_prompt'] = 'force';
            }
        }
        
        return parent::getAuthUrl($callbackUrl, $options);
    }

    /**
     * Get youtube user data
     * @param  AccessToken $token The access token object
     * @return array the user data
     */
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

    /**
     * Get the admin token from database or cached
     * @return AccessToken the access token object for admin requests
     */
    private function getAdminToken(){

        if ($this->adminToken !== null) return $this->adminToken;

        $data = $this->conn->fetchAssoc("SELECT access_token, refresh_token, expires FROM {$this->tables['admin']} WHERE username = ? AND NOT revoked", array($this->getAdmin()));

        if ($data){

            $this->adminToken = new AccessToken();
            $this->adminToken->replace($data);
        }

        return $this->adminToken;
    }

    /**
     * Get valid access token (refreshing token if necessary)
     * @return AccessToken the access token object for admin requests
     */
    public function getValidAdminToken(){

        $token = $this->getAdminToken();

        if (!$this->isAdminTokenValid()) return null;

        if ($this->isAdminTokenExpired()){

            try {
            
                $token = $this->refreshAdminToken();

            } catch (InvalidRefreshTokenException $e){

                return null;
            }
        }

        return $token;
    }

    /**
     * Determine if admin token is available in database and valid
     * @return boolean
     */
    public function isAdminTokenValid(){

        $token = $this->getAdminToken();

        return ($token !== null && $token->getValue() && $token->get('refresh_token'));
    }

    /**
     * Save access token in current session as admin token
     * @return void
     */
    public function saveAdminToken(AccessToken $token){

        if (!$this->isAuthorized()) return;

        $val = $token->getValue();
        $refresh = $token->get('refresh_token');

        if (!$token || !$val || !$refresh) throw new InvalidRefreshTokenException();

        $expires = new \Datetime('now');
        $expires->add( \DateInterval::createFromDateString($token->get('expires_in') . ' seconds') );

        $this->conn->executeQuery(
            "INSERT OR REPLACE INTO {$this->tables['admin']} (username, access_token, refresh_token, expires, revoked) VALUES (?,?,?,?,?)",
            array(
                $this->getYTUserName(),
                $val,
                $refresh,
                $expires->format('c'),
                0,
            )
        );
    }

    /**
     * Determine if admin access token has expired
     * @return boolean
     */
    private function isAdminTokenExpired(){

        $token = $this->getAdminToken();

        if (!$token) throw \Exception('Admin token unavailable');

        $now = new \DateTime('now');
        $expires = new \DateTime($token->get('expires'));

        return ($now > $expires);
    }

    /**
     * Refresh the admin access token and store new one to database
     * @return AccessToken the admin access token
     */
    private function refreshAdminToken(){

        $token = $this->getAdminToken();

        $client = $this->getHttpClient();

        $query['client_id'] = $this->getClientId();
        $query['client_secret'] = $this->getClientSecret();
        $query['grant_type'] = 'refresh_token';
        $query['refresh_token'] = $token->get('refresh_token');

        try {
            
            $response = $this->executeAccessRequest($client, $query);

        } catch (\Guzzle\Http\Exception\BadResponseException $e){

            if ($e->getResponse()->getStatusCode() === 401 || $e->getResponse()->getStatusCode() === 400){

                // set token as revoked
                $this->conn->update(
                    $this->tables['admin'], 
                    array(
                        'revoked' => 1,
                    ),
                    array('username' => $this->getAdmin())
                );

                $this->adminToken = null;

                throw new InvalidRefreshTokenException();
            }
        }

        $parameters = $this->parseAccessResponse($response);

        $token = $this->createAccessToken($parameters);

        $expires = new \Datetime('now');
        $expires->add( \DateInterval::createFromDateString($token->get('expires_in') . ' seconds') );

        $this->conn->update(
            $this->tables['admin'], 
            array(
                'access_token' => $token->getValue(),
                'expires' => $expires->format('c'),
            ),
            array('username' => $this->getAdmin())
        );

        return $this->adminToken = $token;
    }

    /**
     * Is current user allowing youtube access
     * @return boolean
     */
    public function hasYoutubeAuth(){

        $user = $this->session->get('user_object');

        return ($user && null !== $user->get('ytusername'));
    }

    /**
     * Authenticate through youtube for youtube data access
     * @return void
     */
    public function doYoutubeAuth(){

        $this->session->set('youtube_auth', true);
    }
}
