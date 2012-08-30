<?php

use Illuminate\Socialite\OAuthTwo;

$route = $app['controllers_factory'];

$route->get('/', function() use($app) { 
	$username = $app['session']->get('username');

	if ($username == null) {
		return 'Welcome Guest. <a href="'.$app['url_generator']->generate('login').'">Login</a>';
	} else {
		return 'Welcome ' . $app->escape($username);
	}
})->bind('admin');

$route->get('/login', function () use ($app) {
	// check if the user is already logged-in
	if (null !== ($username = $app['session']->get('username'))) {
		return $app->redirect($app['url_generator']->generate('admin'));
	}

	$oauth = new OAuth(CONS_KEY, CONS_SECRET, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
	$request_token = $oauth->getRequestToken('https://twitter.com/oauth/request_token');

	$app['session']->set('secret', $request_token['oauth_token_secret']);

	return $app->redirect('https://twitter.com/oauth/authenticate?oauth_token=' . $request_token['oauth_token']);
})->bind('login');

$route->get('/auth', function() use ($app) {
	// check if the user is already logged-in
	if (null !== ($username = $app['session']->get('username'))) {
		return $app->redirect($app['url_generator']->generate('admin'));
	}

	$oauth_token = $app['request']->get('oauth_token');

	if ($oauth_token == null) {
		$app->abort(400, 'Invalid token');
	}

	$secret = $app['session']->get('secret');

	$oauth = new OAuth(CONS_KEY, CONS_SECRET, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
	$oauth->setToken($oauth_token, $secret);

	try {
		$oauth_token_info = $oauth->getAccessToken('https://twitter.com/oauth/access_token');
	} catch (OAuthException $e) {
		$app->abort(401, $e->getMessage());
	}

	// retrieve Twitter user details
	$oauth->setToken($oauth_token_info['oauth_token'], $oauth_token_info['oauth_token_secret']);
	$oauth->fetch('https://twitter.com/account/verify_credentials.json');
	$json = json_decode($oauth->getLastResponse());

	$app['session']->set('username', $json->screen_name);

	return $app->redirect($app['url_generator']->generate('admin'));
});

return $route;