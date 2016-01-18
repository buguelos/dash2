<?php
error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_DEPRECATED);

require_once(__DIR__.'/../vendor/autoload.php');
require_once(__DIR__.'/../php_incompat.php');

define("MAINDIR", __DIR__.'/../');
define("WEBDIR", __DIR__);
define("MAINHOST", 'http://'.$_SERVER['HTTP_HOST']);
define("MAINURL", substr($_SERVER['SCRIPT_NAME'], 0, -strlen('/index.php')));

require_once(__DIR__.'/../config.php');

session_cache_limiter(false);
session_start();

// View Config
$app = new \Slim\Slim(array(
	'view' => new \Slim\Views\Twig()
));
$app->view->twigTemplateDirs = array(MAINDIR.'/tmpl');
$app->view->set('MAINURL', MAINURL);
$app->notFound(function () use ($app) {
	$app->render('404.html');
});
$twig = $app->view->getInstance();
$twig->addFunction(new Twig_SimpleFunction('wordInString', function ($word, $str) {
	return in_array($word, explode(',', $str));
}));

// Auth configuration
$configStrong = array(
		'provider' => 'PDO',
		'pdo' => \GitGis\Whatsapp\Model\DBConnection::getInstance(),
		'auth.type' => 'form',
		'login.url' => MAINURL.'/auth/login',
		'security.urls' => array(
				array('path' => '/messages/?.*'),
				array('path' => '/inbox/?.*'),
                array('path' => '/groups/?.*'),
                array('path' => '/senders/?.*'),
                array('path' => '/reports/?.*'),
				array('path' => '/users/?.*'),
		),
);
$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware);
$app->add(new \Slim\Extras\Middleware\StrongAuth($configStrong));
$app->add(new \GitGis\Auth\GitGisMiddleware());
$app->get('/auth/login', array('\\GitGis\\Auth\\AuthController', 'getLoginPage'));
$app->post('/auth/login', array('\\GitGis\\Auth\\AuthController', 'postLoginPage'));
$app->get('/auth/logout', array('\\GitGis\\Auth\\AuthController', 'getLogoutPage'));

// Routes
$app->get('/', array('\\GitGis\\Whatsapp\\MainController', 'getPage'));

$app->get('/reports/sent/?', array('\\GitGis\\Whatsapp\\ReportsController', 'getSentPage'));
$app->get('/reports/sent/:page/?', array('\\GitGis\\Whatsapp\\ReportsController', 'getSentPage'))
    ->conditions(array('page' => '[0-9]+'));
$app->get('/reports/inbox/?', array('\\GitGis\\Whatsapp\\ReportsController', 'getInboxPage'));
$app->get('/reports/inbox/:page/?', array('\\GitGis\\Whatsapp\\ReportsController', 'getInboxPage'))
    ->conditions(array('page' => '[0-9]+'));



$app->get('/messages/?', array('\\GitGis\\Whatsapp\\MessagesController', 'getPage'));
$app->get('/messages/:page/?', array('\\GitGis\\Whatsapp\\MessagesController', 'getPage'))
	->conditions(array('page' => '[0-9]+'));
$app->get('/messages/send_text', array('\\GitGis\\Whatsapp\\MessagesController', 'getSendText'));
$app->get('/messages/send_photo', array('\\GitGis\\Whatsapp\\MessagesController', 'getSendPhoto'));
$app->get('/messages/send_video', array('\\GitGis\\Whatsapp\\MessagesController', 'getSendVideo'));
$app->get('/messages/send_audio', array('\\GitGis\\Whatsapp\\MessagesController', 'getSendAudio'));
$app->get('/messages/edit/:id', array('\\GitGis\\Whatsapp\\MessagesController', 'getEditPage'))
	->conditions(array('id' => '[0-9]+'));
$app->get('/messages/delete/:id', array('\\GitGis\\Whatsapp\\MessagesController', 'deletePage'))
	->conditions(array('id' => '[0-9]+'));
$app->post('/messages/edit/:id', array('\\GitGis\\Whatsapp\\MessagesController', 'postEditPage'))
	->conditions(array('id' => '[0-9]+'));
$app->post('/messages/upload/:id', array('\\GitGis\\Whatsapp\\MessagesController', 'postUploadPage'))
    ->conditions(array('id' => '[0-9]+'));
$app->get('/messages/custom_csv/:id', array('\\GitGis\\Whatsapp\\MessagesController', 'getUploadCustomPage'))
    ->conditions(array('id' => '[0-9]+'));
$app->post('/messages/custom_csv/:id', array('\\GitGis\\Whatsapp\\MessagesController', 'postUploadCustomPage'))
    ->conditions(array('id' => '[0-9]+'));

$app->get('/groups', array('\\GitGis\\Whatsapp\\GroupsController', 'getPage'));
$app->get('/groups/:page/?', array('\\GitGis\\Whatsapp\\GroupsController', 'getPage'))
    ->conditions(array('page' => '[0-9]+'));
$app->get('/groups/edit/:id', array('\\GitGis\\Whatsapp\\GroupsController', 'getEditPage'))
	->conditions(array('id' => '[0-9]+'));
$app->post('/groups/edit/:id', array('\\GitGis\\Whatsapp\\GroupsController', 'postEditPage'))
	->conditions(array('id' => '[0-9]+'));
$app->get('/groups/delete/:id', array('\\GitGis\\Whatsapp\\GroupsController', 'deletePage'))
    ->conditions(array('id' => '[0-9]+'));
$app->post('/groups/delete/:id', array('\\GitGis\\Whatsapp\\GroupsController', 'postDeletePage'))
    ->conditions(array('id' => '[0-9]+'));
$app->get('/groups/export_numbers/:id', array('\\GitGis\\Whatsapp\\GroupsController', 'getExportNumbersPage'))
    ->conditions(array('id' => '[0-9]+'));

$app->post('/groups/:id/upload', array('\\GitGis\\Whatsapp\\GroupsController', 'postUploadNumber'))
	->conditions(array('id' => '[0-9]+'));
$app->get('/groups/:id/delete_number', array('\\GitGis\\Whatsapp\\GroupsController', 'getDeleteNumber'))
	->conditions(array('id' => '[0-9]+'));


$app->get('/senders', array('\\GitGis\\Whatsapp\\SendersController', 'getPage'));
$app->get('/senders/:page/?', array('\\GitGis\\Whatsapp\\SendersController', 'getPage'))
    ->conditions(array('page' => '[0-9]+'));
$app->get('/senders/edit/:id', array('\\GitGis\\Whatsapp\\SendersController', 'getEditPage'))
    ->conditions(array('id' => '[0-9]+'));
$app->post('/senders/edit/:id', array('\\GitGis\\Whatsapp\\SendersController', 'postEditPage'))
    ->conditions(array('id' => '[0-9]+'));
$app->get('/senders/delete/:id', array('\\GitGis\\Whatsapp\\SendersController', 'deletePage'))
    ->conditions(array('id' => '[0-9]+'));
$app->post('/senders/delete/:id', array('\\GitGis\\Whatsapp\\SendersController', 'postDeletePage'))
    ->conditions(array('id' => '[0-9]+'));

$app->get('/senders/:id/smscode', array('\\GitGis\\Whatsapp\\SendersController', 'getConfirmPage'))
	->conditions(array('id' => '[0-9]+'));
$app->post('/senders/:id/smscode', array('\\GitGis\\Whatsapp\\SendersController', 'postConfirmPage'))
	->conditions(array('id' => '[0-9]+'));

$app->get('/users', array('\\GitGis\\Whatsapp\\UsersController', 'getPage'));
$app->get('/users/edit/:id', array('\\GitGis\\Whatsapp\\UsersController', 'getEditPage'))
	->conditions(array('id' => '[0-9]+'));
$app->post('/users/edit/:id', array('\\GitGis\\Whatsapp\\UsersController', 'postEditPage'))
	->conditions(array('id' => '[0-9]+'));
$app->get('/users/delete/:id', array('\\GitGis\\Whatsapp\\UsersController', 'deletePage'))
	->conditions(array('id' => '[0-9]+'));

$app->get('/inbox/?', array('\\GitGis\\Whatsapp\\InboxController', 'getPage'));
$app->get('/inbox/:page/?', array('\\GitGis\\Whatsapp\\InboxController', 'getPage'));

$app->run();
