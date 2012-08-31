<?php

use Illuminate\Socialite\OAuthTwo;
use Symfony\Component\HttpFoundation\Request;

require_once YTSE_ROOT.'/include/Pest.php';

$route = $app['controllers_factory'];

$route->get('/', function() use($app) { 
	$username = $app['session']->get('username');

	if ($username == null) {
		return 'Welcome Guest. <a href="'.$app['url_generator']->generate('login').'">Login</a>';
	} else {
		return 'Welcome ' . $app->escape($username) . '(<a href="'.$app['url_generator']->generate('logout').'">Logout</a>)';
	}
})->bind('admin');

$route->get('/login', function (Request $request) use ($app) {
	// check if the user is already logged-in
	if (null !== ($username = $app['session']->get('username'))) {
		return $app->redirect($app['url_generator']->generate('admin'));
	}

	$app['google.oauth']->setScope('https://gdata.youtube.com');

	return $app->redirect($app['google.oauth']->getAuthUrl(
		$app['url_generator']->generate('auth', array(), true)
	));
})->bind('login');

$route->get('/logout', function () use ($app) {

	$app['session']->set('username', null);
	return $app->redirect($app['url_generator']->generate('admin'));
})->bind('logout');

$route->get('/auth', function(Request $request) use ($app) {
	// check if the user is already logged-in
	if (null !== ($username = $app['session']->get('username'))) {
		return $app->redirect($app['url_generator']->generate('admin'));
	}

	$oauth_token = $app['google.oauth']->getAccessToken($request, array(
		'redirect_uri' => $app['url_generator']->generate('auth', array(), true)
		));

	//var_dump($oauth_token);

	if ($oauth_token == null) {
		$app->abort(400, 'Invalid token');
	}

	$pest = new Pest('https://gdata.youtube.com');

	$pest->curl_opts[CURLOPT_HTTPHEADER] = array(
        'Authorization: Bearer ' . $oauth_token->getValue()
    );

    $userdata = json_decode($pest->get('/feeds/api/users/default?alt=json'), true);

    $app['session']->set('username', $userdata['entry']['yt$username']['$t']);

	return $app->redirect($app['url_generator']->generate('admin'));
})->bind('auth');

return $route;