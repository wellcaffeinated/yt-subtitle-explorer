# YouTube Subtitle Explorer

The **YouTube Subtitle Explorer** is a tool you can install on your server to provide a website to display videos from one of your youtube playlists. Users can then search by languages to find videos that contain or don't contain subtitles for a particular language. Users can also easily submit caption files that can be then uploaded directly to youtube which makes maintenance very easy.

**As seen on [Minute Physics Translate](http://translate.minutephysics.com)**.

## Donate

Like the software? Why not buy me a coffee? All donations go first towards maintenance of YTSE, then to future open-source projects (and/or coffee).

<a href="http://flattr.com/thing/928532/wellcaffeinatedyt-subtitle-explorer-on-GitHub" target="_blank">
<img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a>

## Features

* Positive and Negative search for videos by one or more subtitles by language name.
* Watch video with specified captions by clicking on a language.
* Google/YouTube OAuth 2 login authentication. Meaning much less likelihood of getting spam submissions.
* "Help Translate" crowdsourcing feature. So anyone can safely submit subtitle files for any of your videos.
* Administration panel that allows you to moderate caption submissions.
* Caption trash archive (to keep past submissions).
* Email notifications for (consenting) contributors and the administrator.
* Automatic Updates! Update feature allows administrator to update the system with the click of a button.
* Ability to (almost) entirely override the look/feel of the site.
* Maintenance mode.

## Requirements

* Webserver with **PHP 5.3+**, and **SQLite 3**
* Google account and Youtube Account

## Installation

1. Download the latest source from [GitHub](https://github.com/wellcaffeinated/yt-subtitle-explorer/downloads)
2. Extract the zip contents into a folder on your web server.
3. If the folder isn't the root directory, edit the `.htaccess` file contents to point it to the subfolder. (Look for the helpful comments)
4. Visit the directory in a browser and follow the instructions (it will tell you to visit `/install`)

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

**SECURITY NOTE**: if your system ever becomes comprimised, or you suspect it may be comprimised, the above URLs will allow you to *change your client secret* and invalidate API keys. This is the first thing you should do if you think someone has hacked your system.

## Admin Panel

Simply visit `/admin` in your web browser to login to the admin panel.

## Email Notifications

After you have finished installing the app, you can set up email notifications to be sent when you get a new submission. To do this, visit the admin panel and click on the "settings" tab.

## Security

There are `.htaccess` files with "deny from all" rules in certain sensitive directories. These include:

* config/
* app/
* logs/
* user/db/
* user/uploads/
* user/views/

Whatever you do, ensure that these directories are not accessible by the public and are only accessible by the php application code. (as long as the htaccess files are working that should be taken care of already).

Also, please remember that you shouldn't share the API keys. If your system ever becomes compromized you can always invalidate the keys through google, and paste any newly created keys into the `config/config.yaml` file.

If you ever suspect that your system has been compromized (and someone could have downloaded your database), then immediately unauthorize this app from your [google authorized applications and websites page](https://www.google.com/settings/security).

## Automatic Updates

Once the app is installed, you'll be notified in the administrator panel if there are updates. Just remember to **always backup before updating**.

## Change Look/Feel

If you have knowledge of HTML/CSS, you can override any of the templates by creating your own in the `/user/views` directory. Just name your override the same name as you find it in the `/app/views` directory. **Don't change any code in the `/app` directory because you will loose those changes if you upgrade**.

Views are rendered with the [twig templating system](twig.sensiolabs.org/documentation). You will need to understand how to write twig templates before you can make any changes.

Creating new templates will let you change almost all markup on the app. This is also how you should include your own css or js files.

**Tip**: all full-page templates extend the `page-skeleton.twig` template. So if you override that one, it's a great place to place your custom css js includes. Feel free to use your own subdirectories in `/user` to hold your custom js/css.

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
