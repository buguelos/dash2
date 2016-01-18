Developer manual
====================

Intro
---------------------
WhatsApp Messenger allows to mass broadcast whatsapp message to predefined groups of users.

Requirements and components
---------------------
WhatsApp Messenger is built using:

* Cron - cronjob.php is script run every minute. It's responsible for sending messages to whatsapp server
* MySQL - stores data (except attachements)
* PHP - 5.3.2+

PHP Packages
---------------------
WhatsApp uses following PHP dependencies:

### [Slim](http://www.slimframework.com/)
Slim is a PHP micro framework that helps you quickly write simple yet powerful web applications and APIs.

Used features:

* MVC
* Routing
* Authentication
* Error handling

### [Twig](http://twig.sensiolabs.org/)
Twig is a modern template engine for PHP

### [WhatsAPI](https://github.com/venomous0x/WhatsAPI)
WhatsAPI is an opensource library developed by Max Kovaljov.
It's allows communication Whatsapp servers which include registration, message sending and receiving

Files layout
---------------------
There are following directories
* /img, /css, /js - program assets
* /logs - log dir, should be writable 
* /upload - attachments dir (for multimedia files), should be writable
* /src - PHP sources
* /tmpl - Twig templates 
* /vendor - PHP dependecies
* /index.php - main file
* /config.php - config file

Namespaces
---------------------
There are two namespaces used by program:

* GitGis::Auth - authentication functions
* GitGis::Whatsapp - main program functions
# eme
# dash
# dash2
