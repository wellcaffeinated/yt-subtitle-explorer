<?php

if (!defined('YTSE_ROOT')) die('Access Denied');

// uncomment to activate debug mode
define('DEBUG', TRUE);

// path to create sqlite database
define('YTSE_DB_PATH', YTSE_ROOT.'/db/ytse.db');
// table prefix
define('YTSE_DB_PFX', ''); 

// Youtube Playlist to use
define('YT_PLAYLIST', '908547EAA7E4AE74');

// API Keys

// Universal Subtitles username
define('UNISUB_USERNAME', 'well.caffeinated');
// Universal Subtitles API key
define('UNISUB_KEY', '6451b9bf78857a8c7cea36967b8d4f1b56c7cbb5');
// YouTube API Key
define('YOUTUBE_KEY', 'AI39si5ay6Qrl18RfxMT5UAkKtJgPBcdTJrwLtH6_w9tgwkpNa-IJL584LgZLABjRnR51-pIa7C4N9B0ogCZxjTizUPVVOqRxA');


// Google App OAuth consumer key and secret
define('G_OAUTH_KEY', '800287186226.apps.googleusercontent.com');
define('G_OAUTH_SECRET', 'adOMrJ-Kika6t7onDCARC7GB');