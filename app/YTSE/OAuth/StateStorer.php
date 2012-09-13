<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\OAuth;

use Illuminate\Socialite\OAuthTwo\StateStoreInterface;

class StateStorer implements StateStoreInterface {

    private $session;

	function __construct(\Symfony\Component\HttpFoundation\Session\Session $session){

		$this->session = $session;
	}

    /**
     * Get the state from storage.
     *
     * @return string
     */
    public function getState(){

        return $this->session->get('state');
    }

    /**
     * Set the state in storage.
     *
     * @param  string  $state
     * @return void
     */
    public function setState($state){

        $this->session->set('state', $state);
    }

}