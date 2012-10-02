<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

namespace YTSE\OAuth;

class InvalidRefreshTokenException extends \Exception {

	protected $message = 'The refresh token has been revoked.';

	public function __construct($message = null, $code = 0, \Exception $previous = null) {
        
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }	
}