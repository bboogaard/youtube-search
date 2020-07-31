##############
youtube-search
##############

A wordpress plugin to publish lists of youtube videos

************
Dependencies
************

 - PHP 7.0
 - MySQL 5.5
 - Wordpress
 - `Composer`_ (testing)
 - `Subversion`_ (testing)
 - `PHPUnit`_ (testing)
 - `WP-CLI`_ (testing)
 - Nodejs
 
 .. _`Composer`: https://getcomposer.org/
 .. _`Subversion`: https://subversion.apache.org/
 .. _`PHPUnit`: http://phpunit.de/getting-started.html
 .. _`WP-CLI`: http://wp-cli.org/


***********
Installatie
***********

Vanuit plugin-directory::

    composer install
    npm install    


***********
Development
***********

Blok-code compileren::

    npm run build
    
Tests runnen::

    vendor/bin/phpunit
