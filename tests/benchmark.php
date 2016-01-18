<?php

require_once(__DIR__.'/../vendor/autoload.php');

define("MAINDIR", __DIR__.'/../');
require_once(__DIR__.'/../config.php');
			
use \GitGis\Whatsapp\Model\Message;
use \GitGis\Whatsapp\Model\MessageDAO;
use \GitGis\Whatsapp\Model\Group;
use \GitGis\Whatsapp\Model\GroupDAO;
use \GitGis\Whatsapp\Model\WhatsappDAO;


$groupDao = new GroupDAO();
$group = $groupDao->fetch(1);

$messageDao = new MessageDAO();

for ($cnt = 0; $cnt < 200; $cnt++) {
	$message = new Message();
	
	$message->setKind(Message::KIND_TEXT_MSG);
	$message->setCtime(time());
	$message->setUserId(1);
	$message->setData('Test '.$cnt);

	$message->setStime(time());
	$message->setGroupId($group->getId());
	
	$message = $messageDao->save($message);
	
	$messageDao->addStatus($message, Message::MESSAGE_STATUS_TO_SEND);
}
