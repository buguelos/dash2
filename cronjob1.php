<?php


require_once(__DIR__.'/php_incompat.php');

$lockFile = sys_get_temp_dir().'/whatsapp.lock';
$f = fopen($lockFile, "c+");
if (!flock($f, LOCK_EX | LOCK_NB)) {
    echo 'Locked: '.$lockFile."\n";
    exit;
}

require_once(__DIR__.'/vendor/autoload.php');

define("MAINDIR", __DIR__);
$webDir = __DIR__.'/web/';
if (!file_exists($webDir.'/uploads')) {
    $webDir = __DIR__.'/www/';
    if (!file_exists($webDir.'/uploads')) {
        $webDir = __DIR__.'/public_html/';
    }
}

define("WEBDIR", $webDir);
require_once(__DIR__.'/config.php');
			
use \GitGis\Whatsapp\Model\Message;
use \GitGis\Whatsapp\Model\MessageDAO;
use \GitGis\Whatsapp\Model\SenderDAO;
use \GitGis\Whatsapp\Model\GroupDAO;
use \GitGis\Whatsapp\Model\WhatsappDAO;

function hex($str) {
    $retVal = '';
    for ($i = 0; $i < strlen($str); $i++) {
        $retVal .= sprintf("%02X ", ord($str{$i}));
    }

    return $retVal;
}

function logToCronFile($msg) {
    echo date("Y-m-d H:i:s").' '.time()." ".$msg."\n";
    file_put_contents(
        __DIR__.'/logs/whatsapp.'.date('Ymd').'.log',
        date("Y-m-d H:i:s")." ".$msg."\n",
        FILE_APPEND | LOCK_EX
    );
}

ob_start();

$senderDAO = new SenderDAO();
$groupDAO = new GroupDAO();
$messageDAO = new MessageDAO();

$senders = $senderDAO->getList();

foreach ($senders['list'] as $sender) {
    if ('' == $sender->getPassword()) {
        logToCronFile("No password for sender: ".$sender->getUsername());
        continue;
    }

    $sender = $senderDAO->fetch($sender->getId());

    if (($sender->getFlags() & \GitGis\Whatsapp\Model\Sender::FLAG_UNSYNC) > 0) {
        logToCronFile("Syncing ".$sender->getUsername());

        $groups = $groupDAO->getList(array(
            'user_id' => $sender->getUserId()
        ));

        $contacts = array();
        foreach ($groups['list'] as $groupId => $group) {
            $groupDAO->clearSynced($groupId);

            $groupNumbers = $groupDAO->getNumbers($groupId);
            foreach (array_keys($groupNumbers) as $number) {
                if ($number{0} != '+') $number = '+'.$number;
                $contacts[$number] = $number;
            }
        }
        $contacts = array_keys($contacts);

        try {
            $whatsDAO = WhatsappDAO::instance($sender);
            $whatsDAO->syncContacts($sender, $contacts);

            $flags = $sender->getFlags();
            $flags &= ~\GitGis\Whatsapp\Model\Sender::FLAG_UNSYNC;
            $sender->setFlags($flags);

            $senderDAO->save($sender);
        } catch (\Exception $ex) {
            echo $ex;
        }
    }
}

$messagesList = $messageDAO->getList(array('toSend' => time()));
$messages = $messagesList['list'];
usort($messages, function ($a, $b) {
    return $a->getGroupId() - $b->getGroupId();
});

logToCronFile("Fetched messages to send: ".count($messages));
foreach ($messages as $message) {
    $messageDAO->clearRetry($message);

	$groupId = $message->getGroupId();

    $sender = $senderDAO->fetch($message->getSenderId());
    if (!$sender->getId()) {
        continue;
    }
    if ('' == $sender->getPassword()) {
        echo "Empty password for ".$sender->getUsername().' '.$sender->getNickname()."\n";
        $messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, 'Sender not registered - go sender and use confirm SMS function');
        continue;
    }
    logToCronFile("Sending: ".$sender->getUsername().'=>'.$message->getTarget());
    $whatsappDAO = WhatsappDAO::instance($sender);
    $whatsappDAO->sendMessage($message, $sender);
}

foreach ($senders['list'] as $sender) {
    if ('' == $sender->getPassword()) {
        continue;
    }
    $whatsappDAO = WhatsappDAO::instance($sender);
    $whatsappDAO->processPoll($sender);
}

$buff = ob_get_contents();
ob_end_clean();

logToCronFile("\n".str_replace('<br />', "\n", html_entity_decode($buff)));

@unlink($lockFile);
