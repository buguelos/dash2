<?php

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_DEPRECATED);


$config = array();
$config['db'] = array();
$config['db']['host'] = "localhost";
$config['db']['user'] = "root";
$config['db']['pass'] = "silverston";
$config['db']['name'] = "whatsapp";

$config['limits'] = array();
$config['limits']['sendTotal'] = 1000;
$config['limits']['sendGroup'] = 100;
