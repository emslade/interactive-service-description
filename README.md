Installation
=======================

* Run `composer.phar install`
* Add vhost to point to public directory
* Run `./bin/console generate:html /path/to/service.json public`

Add configuration
-----------------
    // config/roles.php
    return array(
        'role' => array(
            'base_url' => 'http://api.example.com',
            'consumer_key' => 'consumer_key',
            'consumer_secret' => 'consumer_secret',
            'token' => 'token',
            'token_secret' => 'token_secret',
        ),
    );
