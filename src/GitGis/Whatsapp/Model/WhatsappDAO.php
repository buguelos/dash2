<?php

namespace GitGis\Whatsapp\Model;

use \GitGis\Whatsapp\Model\Group;
use \GitGis\Whatsapp\Model\GroupDAO;
use \GitGis\Whatsapp\Model\Sender;
use \GitGis\Whatsapp\Model\SenderDAO;
use \GitGis\Whatsapp\Model\WhatsProt;
use \GitGis\Whatsapp\Model\Message;
use \GitGis\Whatsapp\Model\MessageDAO;
use \GitGis\Whatsapp\Model\Chat;
use \GitGis\Whatsapp\Model\ChatDAO;

/**
 * DAO responsible for communicating WhatsApp server
 * 
 */
class WhatsappDAO {

    private $debug = true;
    private $sender;
    private $connected;

    private static $instances = array();
    private $lastSentMessageId = 0;

    protected function __construct(Sender $sender) {
        $this->sender = $sender;
    }

    /**
     * @param Sender $sender
     * @return WhatsappDAO
     */
    public static function instance(Sender $sender) {
        if (!isset(self::$instances[$sender->getUsername()])) {
            self::$instances[$sender->getUsername()] = new WhatsappDAO($sender);
        }
        return self::$instances[$sender->getUsername()];
    }
	
	/**
	 * Is debugging enabled
	 * 
	 * @return boolean
	 */
	public function getDebugBuf() {
		return $this->debug;
	}
	
	/**
	 * Set debugging mode
	 * 
	 * @param boolean $debug
	 */
	public function setDebug($debug) {
		$this->debug = $debug;
	}
	
	public function printDebug($msg) {
		if (!$this->debug) return;
		echo $msg;
	}
	
	
	/**
	 * Tells WhatsApp to send sms code for specified sender MSISDN
	 * 
	 * @param Sender $sender
	 * @return multitype:string
	 */
	public function sendSmsCode() {
        $sender = $this->sender;
		$senderDAO = new SenderDAO();
		
		$identity = urlencode(sha1('fakeimei'.$sender->getId(), true));

		$sender->setIdentity($identity);
		$sender->setPassword('');

		$senderDAO->save($sender);
		
        $w = $this->getWhatsProt($sender, false);
        if (empty($w)) {
            return array();
        }

        $code = $w->codeRequest('sms');

        if ($code->status == 'fail') {
            throw new \Exception(json_encode($code));
        }

        if ($code->status == 'ok' && !empty($code->pw)) {
            $sender->setUsername($code->login);
            $sender->setPassword($code->pw);
            $senderDAO->save($sender);
        }

	}
	
	/**
	 * Based on sender's MSISDN and SMS code fetches identity and password for sender, then store it into DB
	 * 
	 * @param Sender $sender
	 * @param string $smscode
	 * @return array
	 */
	public function confirmSmsCode($smscode) {
        $sender = $this->sender;
        $dao = new SenderDAO();
		
        $w = $this->getWhatsProt($sender);
        if (empty($w)) {
            return;
        }

        try {
            $code = $w->codeRegister($smscode);
        } catch (\Exception $ex) {
            echo $w->getDebugBuf();
            throw $ex;
        }

        if ($code->status == 'ok' && !empty($code->pw)) {
            $sender->setUsername($code->login);
            $sender->setPassword($code->pw);
            $dao->save($sender);
        }
	}

	public function getWhatsProt(Sender $sender, $connectionRequired = true) {
		$username = $sender->getUsername(); // Telephone number including the country code without '+' or '00'.
		$identity = $sender->getIdentity(); // Obtained during registration with this API or using MissVenom (https://github.com/shirioko/MissVenom) to sniff from your phone.
		$nickname = $sender->getNickname(); // This is the username displayed by WhatsApp clients.

        if (!empty($this->whatsProts[$username])) {
            $w = $this->whatsProts[$username];

            if ($connectionRequired && empty($this->connected)) {
                $w->cleanDebug();

                $w->connect();
                $w->loginWithPassword($sender->getPassword());
                $w->sendPassive("false");
                $this->connected = true;

                $w->sendGetServerProperties();

                echo $w->getDebugBuf();
            }
            return $w;
        }

        $groupDAO = new GroupDAO();

        $w = new WhatsProt($username, $identity, $nickname, true);
        $w->cleanDebug();

        $w->eventManager()->bind('onGetSyncResult', function (\SyncResult $syncResult) use ($groupDAO, $sender) {
            $groups = $groupDAO->getList(array(
                'user_id' => $sender->getUserId()
            ));

            foreach (array_keys($syncResult->existing) as $number) {
                if ($number{0} == '+') $number = substr($number, 1);
                foreach ($groups['list'] as $groupId => $group) {
                    $groupDAO->markSynced($groupId, $number);
                }
            }
            foreach ($syncResult->nonExisting as $number) {
                if ($number{0} == '+') $number = substr($number, 1);
                // TODO
            }
        });

        $w->eventManager()->bind('onCredentialsBad', function ($number, $status, $reason) use ($sender) {
            $senderDao = new SenderDAO();

            $senderMod = $senderDao->fetch($sender->getId());
            $senderMod->setPassword('');
            $senderDao->save($senderMod);

            throw new \Exception('Bad credentials for: '.$number.', status: '.$status.', reason: '.$reason, 403);
        });
        $w->eventManager()->bind('onLoginFailed', function ($number) use ($sender) {
            $senderDao = new SenderDAO();

            if ('' != $sender->getPassword()) {
                $wdao = WhatsappDAO::instance($sender);
                $wdao->sendSmsCode();
            } else {
//            $senderMod = $senderDao->fetch($sender->getId());
////            $senderMod->setPassword('');
//            $senderDao->save($senderMod);

            }

            throw new \Exception('Login failed for '.$number, 403);
        });
        $w->eventManager()->bind('onSendMessage', function ($from, $target, $whatsapp_id, $node) {
            $this->lastSentMessageId = $whatsapp_id;

            if (empty($this->currentMessage)) return;

            $dao = new MessageDAO();

            if (empty($target) || is_array($target)) {
                if (!empty($this->currentTargets)) {
                    foreach ($this->currentTargets as $target) {
                        $dao->addStatus($this->currentMessage, Message::MESSAGE_STATUS_SENT, '', $target, $whatsapp_id);
                    }
                }
            } else {
                $target = explode('@', $target);
                $target = $target[0];

                $dao->addStatus($this->currentMessage, Message::MESSAGE_STATUS_SENT, '', $target, $whatsapp_id);
            }
        });
        $w->eventManager()->bind('onGetReceipt', function ($from, $id, $offline, $retry) {
            $target = explode('@', $from);
            $target = $target[0];

            $dao = new MessageDAO();
            $item = $dao->getMessageByWhatsappId($whatsapp_id);
            if (!empty($item)) {
                if (strpos($from, 'broadcast')) {
                    $target = explode('@', $node->getAttribute('participant'));
                    $target = $target[0];

                    if (!empty($target)) {
                        $dao->setMessageTargetStatus($item, $target, Message::MESSAGE_STATUS_RECEIVED_BY_PHONE);
                        $dao->addStatus($item, Message::MESSAGE_STATUS_RECEIVED_BY_PHONE, '', $target, $whatsapp_id);
                    }
                } else {
                    $dao->setMessageTargetStatus($item, $target, Message::MESSAGE_STATUS_RECEIVED_BY_PHONE);
                    $dao->addStatus($item, Message::MESSAGE_STATUS_RECEIVED_BY_PHONE, '', $target, $whatsapp_id);
                }
            }
        });
        $w->eventManager()->bind('onGetAck', function ($from, $whatsapp_id, $class) {
            if ($class != 'message') return;

            $target = explode('@', $from);
            $target = $target[0];

            $dao = new MessageDAO();
            $item = $dao->getMessageByWhatsappId($whatsapp_id);
            if (!empty($item)) {

                if (strpos($from, 'broadcast')) {
                    $dao->getSentTargetsById($whatsapp_id);
                } else {
                    $dao->setMessageTargetStatus($item, $target, Message::MESSAGE_STATUS_RECEIVED_BY_SERVER);
                    $dao->addStatus($item, Message::MESSAGE_STATUS_RECEIVED_BY_SERVER, '', $target, $whatsapp_id);
                }

            }
        });

        $w->eventManager()->bind('onGetMessage', function ($number, $from, $id, $type, $t, $name, $data) {
            if ($type == 'text') {
                $chatDAO = new ChatDAO();

                $from = preg_replace('!@.*!', '', $from);

                $chat = new Chat();
                $chat->setFrom($from);
                $chat->setFromNickname($name);
                $chat->setTo($number);
                $chat->setData($data);
                $chat->setCtime($t);
                $chat->setWhatsappId($id);

                $chatDAO->save($chat);
            }
        });

        if ($connectionRequired && empty($this->connected)) {
            $w->cleanDebug();

            $w->connect();
            $w->loginWithPassword($sender->getPassword());
            $w->sendGetStatuses("True");
            $this->connected = true;

            $w->sendGetServerProperties();

            echo $w->getDebugBuf();
        }

        $this->whatsProts[$username] = $w;

		return $this->whatsProts[$username];
	}
	
	/**
	 * Send message to WhatsApp, then add statuses to DB
	 * 
	 * @param Message $message
	 */
	public function sendMessage(Message $message, Sender $sender) {
		$messageDAO = new MessageDAO();

		if ('' == $sender->getPassword()) {
			$messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, 'Sender not registered - go sender and use confirm SMS function');
			return;
		}

        $statuses = $messageDAO->getStatuses($message);
        $sentOk = array();

        foreach ($statuses as $status) {
            if (empty($status['target'])) {
                continue;
            }

            switch ($status['status']) {
                case Message::MESSAGE_STATUS_SENT:
                case Message::MESSAGE_STATUS_RECEIVED_BY_SERVER:
                case Message::MESSAGE_STATUS_RECEIVED_BY_PHONE:
                    $sentOk[$status['target']] = 1;
                    $messageDAO->setMessageTargetStatus($message, $status['target'], $status['status']);
                    break;
            }
        }

        echo "Already sent to: ".implode(",", array_keys($sentOk))."\n";

        $groupDao = new GroupDAO();
        $syncedNumbers = $groupDao->getNumbers($message->getGroupId(), true);

		try {
			$w = $this->getWhatsProt($sender);
			if (empty($w)) return;

			$targets = explode(',', $message->getTarget());
            $targets = array_combine($targets, $targets);

            $statuses = $messageDAO->getStatuses($message);
            foreach ($statuses as $status) {
                if ($status['status'] == Message::MESSAGE_STATUS_RECEIVED_BY_SERVER) {
                    unset($targets[$status['target']]);
                }
            }

            $filteredTargets = array();
            $notSynced = array();
            $allTargets = array();
            foreach ($targets as $target) {
                if ($target == $sender->getUsername()) continue;
                if (empty($syncedNumbers[$target])) {
                    $notSynced[$target] = $target;
                    continue;
                }
                if (!empty($sentOk[$target])) continue;

                $allTargets[] = $target;
                if (count($filteredTargets) <= 48) {
                    $filteredTargets[] = $target;
                }
            }
            echo "Filtered: ".implode(",", $filteredTargets)."\n";
            echo "Not synced: ".implode(",", array_keys($notSynced))."\n";

            if (empty($filteredTargets)) {
                $messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, "No synced numbers. Sync Group.");
            }

            $targets = $filteredTargets;

            foreach ($allTargets as $target) {
                $messageDAO->setMessageTargetStatus($message, $target, 0);
            }
                               
            $customFields = $messageDAO->getCustomFields($message);

            $this->currentMessage = $message;
            $this->currentTargets = $targets;

            switch ($message->getKind()) {
                case Message::KIND_TEXT_MSG:
                    $data = $message->getData();
                    if (false && strpos($data, '$field') === false) {
                        $w->cleanDebug();
                        $this->lastSentMessageId = 0;
                        sleep(strlen($data)/4);
                        $w->sendBroadcastMessage($targets, $data);
                        $whatsapp_id = $this->lastSentMessageId;
                        $debugMsgReceiver = $w->getDebugBuf();
                        $this->printDebug($w->getDebugBuf());
                        if (!$whatsapp_id) {
                            $messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, $debugMsgReceiver);
                            break;
                        }

                    } else {
                        foreach ($filteredTargets as $target) {
                            $sendRetVal = null;

                            $data = $message->getData();
                            if (!empty($customFields[$target])) {
                                for ($i = 1; $i <6; $i++) {
                                    $data = str_replace('$field'.$i, $customFields[$target]['field'.$i], $data);
                                }
                            }

                            $w->cleanDebug();
                            $this->lastSentMessageId = 0;
                            $w->sendMessageComposing($target);
                            sleep(strlen($data)/4);
                            $w->sendMessage($target, $data);
                            $whatsapp_id = $this->lastSentMessageId;
                            $debugMsgReceiver = $w->getDebugBuf();
                            $this->printDebug($w->getDebugBuf());
                            if (!$whatsapp_id) {
                                $messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, $debugMsgReceiver);
                                continue;
                            }
                        }
                    }

                    break;

                case Message::KIND_PHOTO_MSG:
                    if (false) {
                        $w->cleanDebug();
                        $this->lastSentMessageId = 0;
                        sleep(8);
                        $w->sendBroadcastImage($targets, WEBDIR.'/uploads/'.$message->getData(), false);
                        $whatsapp_id = $this->lastSentMessageId;
                        $debugMsgReceiver = $w->getDebugBuf();
                        $this->printDebug($w->getDebugBuf());
                        if (!$whatsapp_id) {
                            $messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, $debugMsgReceiver);
                            break;
                        }
                    } else {
                        foreach ($filteredTargets as $target) {
                            $sendRetVal = null;
                            $w->cleanDebug();
                            $this->lastSentMessageId = 0;
                            sleep(4);
                            $w->sendMessageImage($target, WEBDIR.'/uploads/'.$message->getData(), false);
                            $whatsapp_id = $this->lastSentMessageId;
                            $debugMsgReceiver = $w->getDebugBuf();
                            $this->printDebug($w->getDebugBuf());
                            if (!$whatsapp_id) {
                                $messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, $debugMsgReceiver);
                                continue;
                            }
                        }
                    }
                    break;

                case Message::KIND_AUDIO_MSG:
                    if (false) {
                        $w->cleanDebug();
                        $this->lastSentMessageId = 0;
                        sleep(8);
                        $w->sendBroadcastAudio($targets, WEBDIR.'/uploads/'.$message->getData(), false);
                        $whatsapp_id = $this->lastSentMessageId;
                        $debugMsgReceiver = $w->getDebugBuf();
                        $this->printDebug($w->getDebugBuf());
                        if (!$whatsapp_id) {
                            $messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, $debugMsgReceiver);
                            break;
                        }
                    } else {
                        foreach ($filteredTargets as $target) {
                            $sendRetVal = null;
                            $w->cleanDebug();
                            $this->lastSentMessageId = 0;
                            sleep(4);
                            $w->sendMessageAudio($target, WEBDIR.'/uploads/'.$message->getData(), false);
                            $whatsapp_id = $this->lastSentMessageId;
                            $debugMsgReceiver = $w->getDebugBuf();
                            $this->printDebug($w->getDebugBuf());
                            if (!$whatsapp_id) {
                                $messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, $debugMsgReceiver);
                                continue;
                            }
                        }
                    }
                    break;

                case Message::KIND_VIDEO_MSG:
                    if (false) {
                        $w->cleanDebug();
                        $this->lastSentMessageId = 0;
                        sleep(8);
                        $w->sendBroadcastVideo($targets, WEBDIR.'/uploads/'.$message->getData(), false);
                        $whatsapp_id = $this->lastSentMessageId;
                        $debugMsgReceiver = $w->getDebugBuf();
                        $this->printDebug($w->getDebugBuf());
                        if (!$whatsapp_id) {
                            $messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, $debugMsgReceiver);
                            break;
                        }
                    } else {
                        foreach ($filteredTargets as $target) {
                            $sendRetVal = null;
                            $w->cleanDebug();
                            $this->lastSentMessageId = 0;
                            sleep(4);
                            $w->sendMessageVideo($target, WEBDIR.'/uploads/'.$message->getData(), false);
                            $whatsapp_id = $this->lastSentMessageId;
                            $debugMsgReceiver = $w->getDebugBuf();
                            $this->printDebug($w->getDebugBuf());
                            if (!$whatsapp_id) {
                                $messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, $debugMsgReceiver);
                                continue;
                            }
                        }
                    }
                    break;
            }

            $messageDAO->setMessageTargetStatus($message, $status['target'], Message::MESSAGE_STATUS_SENT);

        } catch (\Exception $ex) {
			$this->printDebug($w->getDebugBuf());
			$messageDAO->addStatus($message, Message::MESSAGE_STATUS_ERROR, $ex->getMessage());
		}

        $this->currentTargets = null;
        $this->currentMessage = null;
	}

	/**
	 * Polls for incomming messages for each sender
	 */
	public function processPoll($sender) {
        $debugMsg = '';
        try {
            $w = $this->getWhatsProt($sender);
            if (empty($w)) return;

            $w->cleanDebug();
            $w->pollMessage();
            $debugMsg = $w->getDebugBuf();
        } catch (\Exception $ex) {
            echo ($ex);
        }

        $this->printDebug($debugMsg);
	}
	
	/**
	 * Sync group contacts
	 */
	public function syncContacts($sender, $contacts) {
        $w = $this->getWhatsProt($sender);

        if (empty($w)) throw new \Exception('Error logging to Whatsapp');

        $w->cleanDebug();
        try {
            $w->sendSync($contacts);
            $w->pollMessage();

            $this->printDebug($w->getDebugBuf());
        } catch (\Exception $ex) {
            $this->printDebug($w->getDebugBuf());

            throw $ex;
        }
	}

}
