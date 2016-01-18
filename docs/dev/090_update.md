Updating dependencies
====================
Dependencies are managed by:

* [Composer](http://getcomposer.org/) for PHP packages
* [Git Submodule](http://git-scm.com/docs/git-submodule) for WhatsAPI code

To upgrade PHP packages run:

    ./composer.phar update

In case of any problems remove all files/dirs from /vendor except WhatsAPI

To upgrade WhatsAPI run:

    git submodule foreach git pull
