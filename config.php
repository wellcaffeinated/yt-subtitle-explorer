<?php
/**
 * YouTube Subtitle Explorer
 * 
 * @author  Jasper Palfree <jasper@wellcaffeinated.net>
 * @copyright 2012 Jasper Palfree
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

if (!defined('YTSE_ROOT')) die('Access Denied');

// uncomment to activate debug mode
//define('DEBUG', TRUE);

// path to create sqlite database
define('YTSE_DB_PATH', YTSE_ROOT.'/app/db/ytse.db');
// table prefix
define('YTSE_DB_PFX', ''); 

define('CAPTION_DIR', YTSE_ROOT.'/app/uploads');

// Youtube username of the administrator
define('ADMIN_YT_USERNAME', 'caffeinatedphysicist');
// Youtube Playlist to use
define('YT_PLAYLIST', 'PLB4B992CFA7A233BC');//'908547EAA7E4AE74');
// the default language code to highlight
define('YT_PLAYLIST_DEFAULT_LANG', 'en');

// API Keys

// YouTube API Key
define('YOUTUBE_KEY', 'AI39si5ay6Qrl18RfxMT5UAkKtJgPBcdTJrwLtH6_w9tgwkpNa-IJL584LgZLABjRnR51-pIa7C4N9B0ogCZxjTizUPVVOqRxA');

// Google App OAuth consumer key and secret
define('G_OAUTH_KEY', '800287186226.apps.googleusercontent.com');
define('G_OAUTH_SECRET', 'adOMrJ-Kika6t7onDCARC7GB');