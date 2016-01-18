Config file
====================
Config is stored in config.php

* MAINHOST is a URL to host base without subdir path, example: 'http://example.com'
* MAINURL is a directory where script is stored. For main directory it's empty. For directory http://example.com/whatsapp it would be '/whatsapp'
* $config['db'] is a DB config - put there credentials created while creating Database
* $config['limits']['sendTotal'] is a maximum number of messages to send during cron run
* $config['limits']['sendGroup'] is a maximum number of messages per group to send during cron run, it prevents spamming Whatsapp
