Installation
=======================

* Run `composer.phar install`
* Add vhost to point to public directory
* Run `./bin/console generate:html /path/to/service.json public`

Add configuration
-----------------
    // config/config.php
    return array(
        'roles' => array(
            'role' => array(
                'base_url' => 'http://api.example.com',
                'consumer_key' => 'consumer_key',
                'consumer_secret' => 'consumer_secret',
                'token' => 'token',
                'token_secret' => 'token_secret',
            ),
        ),
        'serviceDescriptionPath' => '/path/to/service/description.json',
        'defaultRole' => 'role',
    );

Contributing
------------

Service Docs uses [Sass](http://sass-lang.com), specifically the SCSS (Sassy CSS) syntax. If you wish to write CSS you’ll 
first need to [install Sass](http://sass-lang.com/install).

With Sass installed, run `sass --cache-location cache/ --style compressed --watch public/stylesheets/application.scss:public/css/application.css` 
to have Sass watch the application file and update the CSS whenever it changes.

Credits
-------

Icons by [Jason Tropp](http://thenounproject.com/term/form/25603/), [Stephen Boak](http://thenounproject.com/term/network/5499/), and [Eric Miller](http://thenounproject.com/term/book/16590/) all from the [Noun Project](http://thenounproject.com).
