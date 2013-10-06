---
layout: default
title: Service Docs
---

Generate HTML from your Guzzle service description.

[Guzzle](http://guzzlephp.org) is a superb PHP HTTP client.

Read more about [Guzzle service descriptions](http://guzzlephp.org/webservice-client/guzzle-service-descriptions.html).

### Getting Started

* Download the repository
* Setup a vhost to point to `/path/to/repository/public`
* `php composer.phar install`
* Generate HTML to public directory

```
./bin/console generate:html /path/to/service.json public
```

### Demo

View the [demo](http://demo.adeslade.co.uk). Demo HTML has been generated from this [service description ](https://gist.github.com/adeslade/6854008).
