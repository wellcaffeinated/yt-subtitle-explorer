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

## Contributing

Please feel free to send pull requests my way. Dependencies are entirely managed by [composer](http://getcomposer.org/).

To build the javascript, use the [RequireJS optimiser](http://requirejs.org/docs/optimization.html) and run:

```bash
	cd library/js
	r.js -o require-profile.js
```

## Credit

This was created by Jasper Palfree (http://wellcaffeinated.net).

Copyright 2012 Jasper Palfree

Licensed under MIT License
