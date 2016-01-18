<?php

namespace GitGis\Whatsapp;

use \GitGis\Whatsapp\Model\Message;
use \GitGis\Whatsapp\Model\MessageDAO;
use \GitGis\Whatsapp\Model\GroupDAO;
use \GitGis\Whatsapp\Model\SenderDAO;
use \GitGis\Whatsapp\Model\UserDAO;
use \GitGis\Pager;

/**
 * Message controller
 */
class MessagesController {
	
	/**
	 * Get list of messages 
	 */
	public static function getPage($page = 0) {
		$app = \Slim\Slim::getInstance();
		$dao = new MessageDAO();
		$userDAO = new UserDAO();
		$users = $userDAO->getList();
		$groupDAO = new GroupDAO();
		$groupsQuery = array();
        $senderDAO = new SenderDAO();
        $sendersQuery = array();
		if (!$userDAO->hasRole('ADMIN')) {
			$strong = \Strong\Strong::getInstance();
			$user = $strong->getUser();
			$groupsQuery['user_id'] = $user['id'];
            $sendersQuery['user_id'] = $user['id'];
        }

        $groups = $groupDAO->getList($groupsQuery);
        if (0 == $groups['total']) {
            return $app->redirect(MAINURL.'/groups');
        }
        $senders = $senderDAO->getList($sendersQuery);
        if (0 == $senders['total']) {
            return $app->redirect(MAINURL.'/senders');
        }

		$app->expires(time());
		
		$query = $_GET;
		if (!$userDAO->hasRole('ADMIN')) {
			$strong = \Strong\Strong::getInstance();
			$user = $strong->getUser();
			$query['user_id'] = $user['id'];
		}
		$pager = new Pager(MAINURL.'/messages/', 25);
		$pager->setPage($page);
		$query = $pager->getQueryArray($query);
		$list = $dao->getList($query);
		$pager->setCount(count($list['list']));
		if (isset($list['total'])) $pager->setTotal($list['total']);

		foreach ($list['list'] as $k => $v) {
			$list['list'][$k]->dataHuman = self::getHumanUrl($v);
		}

        $app->view->set('menu', 'messages');
		$app->view->set('result', $list);
		$app->view->set('pager', $pager);
        $app->view->set('groups', $groups);
        $app->view->set('senders', $senders);
		$app->view->set('users', $users);

		$app->render('messages/list.twig.html');
	}
	
	/**
	 * Get abbreviated link to file connected to multimedia message
	 * 
	 * @param Message $item
	 * @return string
	 */
	public static function getHumanUrl(Message $item) {
		$data = $item->getData();
		$prefix = ($item->getId() % 100).'/'.$item->getId().'.';
		if (substr($data, 0, strlen($prefix)) == $prefix) {
			$data = substr($data, strlen($prefix));
		}
		return $data;
	}
	
	/**
	 * Deletes message
	 */
	public static function deletePage($id) {
		$app = \Slim\Slim::getInstance();
		$dao = new MessageDAO();
		$dao->delete($id);

		return $app->redirect(MAINURL.'/messages');
	}
	
	/**
	 * Get message edit form 
	 */
	public static function getEditPage($id) {
		$app = \Slim\Slim::getInstance();
		$dao = new MessageDAO();
		$userDAO = new UserDAO();
        $groupDAO = new GroupDAO();
        $groupsQuery = array();
        $senderDAO = new SenderDAO();
        $sendersQuery = array();

        $strong = \Strong\Strong::getInstance();
        $user = $strong->getUser();
        $groupsQuery['user_id'] = $user['id'];
        $sendersQuery['user_id'] = $user['id'];


        $groups = $groupDAO->getList($groupsQuery);
        if (0 == $groups['total']) {
            return $app->redirect(MAINURL.'/groups');
        }
        $senders = $senderDAO->getList($sendersQuery);
        if (0 == $senders['total']) {
            return $app->redirect(MAINURL.'/senders');
        }
        $allGroups = $groupDAO->getList();
        $allSenders = $senderDAO->getList();

		if (!$userDAO->hasRole('ADMIN')) {
// 			return $app->status(403);
		}
		
		$app->expires(time());
	
		$item = $dao->fetch($id);
		if (empty($item)) {
			return $app->notFound();
		} else {
			if (!$userDAO->hasRole('ADMIN') && $item->getGroupId()>0 && !in_array($item->getGroupId(), array_keys($groups['list']))) {
	 			return $app->status(403);
			}		
		}

		$statuses = $dao->getStatuses($item);

        $tableTargets = array();
        $hasErrors = false;
        $syncedNumbers = $groupDAO->getNumbers($item->getGroupId(), true);
        $targetTotal = array();
        foreach (array_keys($syncedNumbers) as $target) {
            $targetTotal[$target] = $target;
        }
        $targetSent = array();
        $targetReceived = array();
        if (!empty($statuses)) {
            foreach ($statuses as $status) {

                if (!empty($status['target'])) {
                    $target = $status['target'];

                    if ($status['status'] == Message::MESSAGE_STATUS_RECEIVED_BY_SERVER) {
                        $targetReceived[$status['target']] = $status['target'];
                    }
                    if ($status['status'] == Message::MESSAGE_STATUS_RECEIVED_BY_PHONE) {
                        $targetReceived[$status['target']] = $status['target'];
                    }
                    if ($status['status'] == Message::MESSAGE_STATUS_SENT) {
                        $targetSent[$status['target']] = $status['target'];
                    }

                    switch ($status['status']) {
                        case Message::MESSAGE_STATUS_SENT:
                        case Message::MESSAGE_STATUS_RECEIVED_BY_SERVER:
                        case Message::MESSAGE_STATUS_RECEIVED_BY_PHONE:
                            if (empty($tableTargets[$target]) || $tableTargets[$target] < $status['status']) {
                                $tableTargets[$target] = $status['status'];
                            }
                            break;
                    }
                }
                if ($status['status'] == Message::MESSAGE_STATUS_ERROR) {
                    $hasErrors = true;
                }
            }
        }

        foreach ($targetTotal as $target) {
            if (empty($tableTargets[$target])) {
                $tableTargets[$target] = '';
            }
        }

        if ($item->getStime() == 0) {
            $item->setStime(time());
        }

        $item->dataHuman = self::getHumanUrl($item);
		$mime = '*/*';
		if (Message::KIND_PHOTO_MSG == $item->getKind()) {
            $mime = 'image/*';
        }
		if (Message::KIND_AUDIO_MSG == $item->getKind()) {
            $mime = 'audio/*';
        }
		if (Message::KIND_VIDEO_MSG == $item->getKind()) {
            $mime = 'video/*';
        }

        $sender = $senderDAO->fetch($item->getSenderId());
        if (!empty($sender) && ('' == $sender->getPassword())) {
            $app->view->set('senderToConfirm', $sender);
        }

        $app->view->set('KIND_TEXT_MSG', Message::KIND_TEXT_MSG);
		$app->view->set('KIND_PHOTO_MSG', Message::KIND_PHOTO_MSG);
		$app->view->set('KIND_AUDIO_MSG', Message::KIND_AUDIO_MSG);
		$app->view->set('KIND_VIDEO_MSG', Message::KIND_VIDEO_MSG);
		
		$app->view->set('menu', 'messages');
		$app->view->set('id', $id);
		$app->view->set('item', $item);

		$app->view->set('allGroups', $allGroups);
        $app->view->set('allSenders', $allSenders);
		$app->view->set('groups', $groups);
        $app->view->set('senders', $senders);
        $app->view->set('sender', $sender);

        $app->view->set('tableTargets', $tableTargets);
		$app->view->set('statuses', $statuses);
        $app->view->set('hasErrors', $hasErrors);
        $app->view->set('noSent', count($targetSent));
        $app->view->set('noReceived', count($targetReceived));
        $app->view->set('noTotal', count($targetTotal));
		$app->view->set('mime', $mime);
		
		$app->render('messages/edit.twig.html');
	}
	
	/**
	 * Process upload multimedia file
	 */
	public static function postUploadPage($id) {
		$app = \Slim\Slim::getInstance();
		$dao = new MessageDAO();
		
		$item = $dao->fetch($id);
		if (empty($item)) {
			return $app->notFound();
		}
		$statuses = $dao->getStatuses($item);

		if (!empty($_FILES['file'])) {
			$dirPrefix = $id % 100;
			if (!file_exists(WEBDIR.'/uploads/'.$dirPrefix)) {
				mkdir(WEBDIR.'/uploads/'.$dirPrefix);
			}
			$fileName = $id.'.'.$_FILES['file']['name'];
			move_uploaded_file($_FILES['file']['tmp_name'], WEBDIR.'/uploads/'.$dirPrefix.'/'.$fileName);
			
			$item->setData($dirPrefix.'/'.$fileName);
			$dao->save($item);
		}
	}

    public static function getUploadCustomPage($id) {
        $app = \Slim\Slim::getInstance();
        $dao = new MessageDAO();

        $item = $dao->fetch($id);
        if (empty($item)) {
            return $app->notFound();
        }

        if (!empty($_GET['group_id'])) {
            $item->setGroupId($_GET['group_id']);
        }

        $groupDAO = new GroupDAO();
        $numbers = $groupDAO->getNumbers($item->getGroupId());
        $customFields = $dao->getCustomFields($item);

        header('Content-type: text/csv');
        header('Content-disposition: attachment; filename="custom_'.$id.'.csv"');

        foreach ($customFields as $target => $fields) {
            if (empty($numbers[$target])) continue;

            echo $target;
            foreach ($fields as $v) {
                echo ";".$v;
            }
            echo "\n";
        }
        foreach ($numbers as $target => $fields) {
            if (!empty($customFields[$target])) continue;

            echo $target.";;;;;\n";
        }
    }

    /**
     * Process upload csv file with personalized fields
     */
    public static function postUploadCustomPage($id) {
        $app = \Slim\Slim::getInstance();
        $dao = new MessageDAO();

        $item = $dao->fetch($id);
        if (empty($item)) {
            return $app->notFound();
        }

        if (!empty($_FILES['file'])) {
            $groupDAO = new GroupDAO();
            $numbers = $groupDAO->getNumbers($item->getGroupId());

            $contents = file_get_contents($_FILES['file']['tmp_name']);

            $dao->clearCustomFields($item);

            $contents = explode("\n", $contents);
            foreach ($contents as $row) {
                $cells = explode(";", $row);
                $msisdn = array_shift($cells);
                $msisdn = preg_replace('![^0-9]*!', '', $msisdn);

                $dao->addCustomFields($item, $msisdn, $cells);
            }
        }
    }

	/**
	 * Process message form
	 */
	public static function postEditPage($id) {
		$app = \Slim\Slim::getInstance();
		$dao = new MessageDAO();
		$userDAO = new UserDAO();
		
		$strong = \Strong\Strong::getInstance();
		$user = $strong->getUser();
		$user = $userDAO->fetch($user['id']);

        $groupDAO = new GroupDAO();
        $groupsQuery = array();
        $senderDAO = new SenderDAO();
        $sendersQuery = array();

        $strong = \Strong\Strong::getInstance();
        $user2 = $strong->getUser();
        $groupsQuery['user_id'] = $user2['id'];
        $sendersQuery['user_id'] = $user2['id'];

        $groups = $groupDAO->getList($groupsQuery);
        if (0 == $groups['total']) {
            return $app->redirect(MAINURL.'/groups');
        }
        $senders = $senderDAO->getList($sendersQuery);
        if (0 == $senders['total']) {
            return $app->redirect(MAINURL.'/senders');
        }
	
		$app->view->set('KIND_TEXT_MSG', Message::KIND_TEXT_MSG);
		$app->view->set('KIND_PHOTO_MSG', Message::KIND_PHOTO_MSG);
		$app->view->set('KIND_AUDIO_MSG', Message::KIND_AUDIO_MSG);
		$app->view->set('KIND_VIDEO_MSG', Message::KIND_VIDEO_MSG);
		
		$item = $dao->fetch($id);
		if (empty($item)) {
			return $app->notFound();
		} else {
			if (!$userDAO->hasRole('ADMIN') && $item->getGroupId()>0 && !in_array($item->getGroupId(), array_keys($groups['list']))) {
				return $app->status(403);
			}
			if (empty($_POST['data'])) $_POST['data'] = $item->getData();
		}

        $statuses = $dao->getStatuses($item);

        if (!empty($_POST['resend'])) {
            $dao->resend($item);
            $app->redirect(MAINURL.'/messages/edit/'.$item->getId());
            return;
        }

        $item->dataHuman = self::getHumanUrl($item);
		$mime = '*/*';
		if (Message::KIND_PHOTO_MSG == $item->getKind()) {
			$mime = 'image/*';
		}
		if (Message::KIND_AUDIO_MSG == $item->getKind()) {
			$mime = 'audio/*';
		}
		if (Message::KIND_VIDEO_MSG == $item->getKind()) {
			$mime = 'video/*';
		}

		$item->setUserId($user->getId());
	
		$dateParts = explode('-', $_POST['stime_date']);
		$timeParts = explode(':', $_POST['stime_time']);
		$stime = mktime($timeParts[0], $timeParts[1], $timeParts[2], $dateParts[1], $dateParts[2], $dateParts[0]);
		$item->setStime($stime);
		if ($item->getKind() == Message::KIND_TEXT_MSG) {
			$item->setData($_POST['data']);
		}
        $item->setGroupId($_POST['group_id']);
        $item->setSenderId($_POST['sender_id']);

        $personalized = array();

        $customField = $dao->getCustomFields($item);
        $numbers = $groupDAO->getNumbers($item->getGroupId());

        foreach ($customField as $num => $fields) {
            if (empty($numbers[$num])) continue;
            $personalized[$num] = $num;
        }

        $_POST['personalized'] = 'personalized $validator';

        $validator = new \Valitron\Validator($_POST);
		$validator->addRule('credits', function ($name, $value) use ($user, $userDAO) {
			if ($userDAO->hasRole('ADMIN')) return true;
			
			return $user->getCredits() > 0;
		});
		$validator->addRule('time', function ($name, $value) {
			$value = explode(':', $value);
			if (count($value)!=3) return false;
			
			return true;
		});
        $validator->addRule('personalized', function ($name, $value) use ($personalized, $numbers) {
            if (count($personalized) == 0) return true;

            return count($personalized) == count($numbers);
        });

        $validator->rule('personalized', 'personalized');
        $validator->label('Personalized');

        $validator->rule('required', 'group_id');
        $validator->label('Group');
        $validator->rule('required', 'sender_id');
        $validator->label('Sender');
		$validator->rule('date', 'stime_date');
		$validator->label('Date');
		$validator->rule('time', 'stime_time');
		$validator->label('Time');
		$validator->rule('required', 'data');
        $validator->label('Message');

		$validator->rule('credits', 'credits');
		$validator->label('Credits');

		if($validator->validate()) {
			$item = $dao->save($item);
			if (!empty($_POST['send']) && empty($statuses)) {
				$dao->addStatus($item, Message::MESSAGE_STATUS_TO_SEND);
				if (!$userDAO->hasRole('ADMIN')) {
                    $numbers = $groupDAO->getNumbers($item->getGroupId());

					$user->setCredits($user->getCredits()-count($numbers));
					$userDAO->save($user);
				}
			}
			
			$app->redirect(MAINURL.'/messages/edit/'.$item->getId());
		} else {
			$app->view->set('menu', 'messages');
			$app->view->set('id', $id);
            $app->view->set('item', $item);
            $app->view->set('numbers', $numbers);
            $app->view->set('numbers_count', count($numbers));
            $app->view->set('personalized', $personalized);
            $app->view->set('personalized_count', count($personalized));
			$app->view->set('groups', $groups);
            $app->view->set('senders', $senders);
			$app->view->set('statuses', $statuses);
			$app->view->set('errors', $validator->errors());
			$app->view->set('mime', $mime);
	
			$app->render('messages/edit.twig.html');
		}
	}
	
	/**
	 * Creates text message and redirects to edit 
	 */
	public static function getSendText() {
		$app = \Slim\Slim::getInstance();
		$dao = new MessageDAO();
		
		$item = new Message();
		
		$item->setKind(Message::KIND_TEXT_MSG);
		$item->setCtime(time());
		$strong = \Strong\Strong::getInstance();
		$user = $strong->getUser();
		$item->setUserId($user['id']);
		
		$item = $dao->save($item);
		
		$app->redirect(MAINURL.'/messages/edit/'.$item->getId());
	}

	/**
	 * Creates photo message and redirects to edit 
	 */
	public static function getSendPhoto() {
		$app = \Slim\Slim::getInstance();
		$dao = new MessageDAO();
	
		$item = new Message();
	
		$item->setKind(Message::KIND_PHOTO_MSG);
		$item->setCtime(time());
		$strong = \Strong\Strong::getInstance();
		$user = $strong->getUser();
		$item->setUserId($user['id']);
		
		$item = $dao->save($item);
	
		$app->redirect(MAINURL.'/messages/edit/'.$item->getId());
	}

	/**
	 * Creates audio message and redirects to edit 
	 */
	public static function getSendAudio() {
		$app = \Slim\Slim::getInstance();
		$dao = new MessageDAO();
	
		$item = new Message();
	
		$item->setKind(Message::KIND_AUDIO_MSG);
		$item->setCtime(time());
		$strong = \Strong\Strong::getInstance();
		$user = $strong->getUser();
		$item->setUserId($user['id']);
		
		$item = $dao->save($item);
	
		$app->redirect(MAINURL.'/messages/edit/'.$item->getId());
	}
	
	/**
	 * Creates video message and redirects to edit
	 */
	public static function getSendVideo() {
		$app = \Slim\Slim::getInstance();
		$dao = new MessageDAO();
	
		$item = new Message();
	
		$item->setKind(Message::KIND_VIDEO_MSG);
		$item->setCtime(time());
		$strong = \Strong\Strong::getInstance();
		$user = $strong->getUser();
		$item->setUserId($user['id']);
		
		$item = $dao->save($item);
	
		$app->redirect(MAINURL.'/messages/edit/'.$item->getId());
	}
	
}
