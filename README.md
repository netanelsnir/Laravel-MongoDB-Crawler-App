# Laravel Crawler

This is a simple [Laravel](https://laravel.com/docs/9.x) application created with its built-in
solution [Sail](https://laravel.com/docs/9.x/sail) for running your Laravel project
using [Docker](https://www.docker.com/)

## Requirements for building and running the application (Docker/Manual)

- PHP 8.0.2+
- [Composer](https://getcomposer.org/download/)

1. Docker:
    - [Docker](https://docs.docker.com/get-docker/)
    
2. Or Manual:
    - Make sure you have the MongoDB PHP driver installed. You can find installation instructions at http://php.net/manual/en/mongodb.installation.php
    or
    https://www.mongodb.com/compatibility/mongodb-laravel-intergration


## Application Build and Run

After cloning the repository get into the project directory and run:

For Docker:
`php composer install`

`./vendor/bin/sail up -d`

`./vendor/bin/sail composer require jenssegers/mongodb`

`./vendor/bin/sail down`

`./vendor/bin/sail up -d`

Go to [http://localhost](http://localhost) in order to see the application running.


For Manual:
`php composer install`

`composer require jenssegers/mongodb`

`php artisan serve`

## Then finally test the application

Go to [http://localhost](http://localhost) in order to see the application running.


## ENV Configuration

MongoDB Database Name:
DB_DATABASE=laravel_sail

MongoDB Connection String:
MONGO_DB_DSN=mongodb://mongo:27017

Timeout for each http crawel request:
HTTP_TIMEOUT_SECONDS=2