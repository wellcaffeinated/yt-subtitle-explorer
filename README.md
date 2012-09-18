# YouTube Subtitle Explorer

The **YouTube Subtitle Explorer** is a tool you can install on your website to display a videos from one of your playlists. Users can then search by languages to find videos that contain or don't contain subtitles for a particular language. Users can also easily submit caption files that can be then uploaded directly to youtube.

## Features

* Positive and Negative search for videos by one or more subtitles by language name.
* "Help Translate" crowdsourcing feature. So anyone can safely submit subtitle files for any of your videos.
* Administration panel that allows you to moderate caption submissions.
* All authentication is done through Google's OAuth 2.0 API. Meaning much less likelihood of getting spam submissions.

## Requirements

* Webserver with **PHP 5.3+**, and **SQLite**
* Google account and Youtube Account

## Installation

1. Download the source from [GitHub](https://github.com/wellcaffeinated/yt-subtitle-explorer)
2. Extract the zip contents into a folder on your web server.
3. Change the name of `htaccess.txt` to `.htaccess`
4. If the folder isn't the root directory, edit the `.htaccess` file contents to point it to the subfolder.
5. Visit the directory in a browser and follow the instructions (it will tell you to visit `/install`)

### API Keys

You will need to create your own developer API keys to install this app. You will need a google Project with API OAuth access and a YouTube developer key.

For Google:

1. Visit [google's api console](https://code.google.com/apis/console).
2. Create a new project (give it a name you like)
3. Click on "API Access"
4. Click "Create an OAuth 2.0 client ID..."
5. Enter the product name: "YTSE"
6. Select Application Type = "Web Application"
7. In the url input, enter the url to get to the app, followed by login/authenticate/callback (eg: if your installation is on example.com in the subfolder translations/, you would enter: example.com/translations/login/authenticate/callback)

For YouTube:

1. Visit [YouTube's API Dashboard](https://code.google.com/apis/youtube/dashboard).
2. Click to create a "New Product"
3. Enter a name like "YTSE"
4. Click save

## Security

There are `.htaccess` files with "deny from all" rules in certain sensitive directories. These include:

* config/
* app/
* logs/

Whatever you do, ensure that these directories are not accessible by the public and are only accessible by the php application code. (as long as the htaccess files are working that should be taken care of already).

Also, please remember that you shouldn't share the API keys. If your system ever becomes compromized you can always invalidate the keys through google, and paste any newly created keys into the `config/config.yaml` file.

If you ever suspect that your system has been compromized (and someone could have downloaded your database), then immediately unauthorize this app from your [google authorized applications and websites page](https://www.google.com/settings/security).

*Note: no contributor user information is stored permanently by the app. Only administrator information is stored.**

## Contributing

Please feel free to send pull requests my way. Dependencies are entirely managed by [composer](http://getcomposer.org/).

To build the javascript, use the [RequireJS optimiser](http://requirejs.org/docs/optimization.html) and run:

```bash
	cd library/js
	r.js -o require-profile.js
```

## Credit

This was created and developed by Jasper Palfree of *Well Caffeinated* (http://wellcaffeinated.net) with creative direction from Henry Reich of *Minute Physics* (http://minutephysics.com).

Copyright 2012 Jasper Palfree

Licensed under MIT License
