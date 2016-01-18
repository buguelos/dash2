<?php

namespace GitGis\Whatsapp;

use \GitGis\Whatsapp\Model\Sender;
use \GitGis\Whatsapp\Model\SenderDAO;
use \GitGis\Whatsapp\Model\UserDAO;
use \GitGis\Whatsapp\Model\GroupDAO;
use \GitGis\Pager;

/**
 * Groups controller
 *
 */
class GroupsController {

	/**
	 * Get list of groups
	 * 
	 * @param number $page
	 */
	public static function getPage($page = 0) {
		$app = \Slim\Slim::getInstance();
		$dao = new GroupDAO();
		$userDAO = new UserDAO();

		$app->expires(time());

        $strong = \Strong\Strong::getInstance();
        $userStrong = $strong->getUser();

        $query = $_GET;
		if (!$userDAO->hasRole('ADMIN')) {
			$query['user_id'] = $userStrong['id'];
		}
		$pager = new Pager(MAINURL.'/groups/', 25);
		$pager->setPage($page);
		$query = $pager->getQueryArray($query);
		$list = $dao->getList($query);
		$pager->setCount(count($list['list']));
		if (isset($list['total'])) $pager->setTotal($list['total']);

        $ownerList = $dao->getList(array(
            user_id => $userStrong['id']
        ));
		
		$app->view->set('menu', 'groups');
		$app->view->set('query', $query);
        $app->view->set('result', $list);
        $app->view->set('user_result', $ownerList);
		$app->view->set('pager', $pager);
        $app->view->set('users', $userDAO->getList());

        $app->render('groups/list.twig.html');
	}

    public static function getExportNumbersPage($id) {
        $dao = new GroupDAO();

        $numbers = $dao->getNumbers($id);
        $mode = $_GET['mode'];

        $numbersFiltered  = array();

        switch ($mode) {
            case 'registered':
                foreach ($numbers as $k => $v) {
                    if (!empty($v['synced'])) {
                        $numbersFiltered[$k] = $v;
                    }
                }
                break;

            case 'unregistered':
                foreach ($numbers as $k => $v) {
                    if (empty($v['synced'])) {
                        $numbersFiltered[$k] = $v;
                    }
                }
                break;

            default:
                $numbersFiltered = $numbers;
        }

        header('Content-type: text/csv');
        header('Content-disposition: attachment; filename="numbers_'.(!empty($mode) ? $mode.'_' : '').''.$id.'.csv"');

        foreach ($numbersFiltered as $k => $v) {
            echo $v['target'].",".$v['nickname']."\n";
        }
        exit;
    }

	/**
	 * Get edit group form
	 * 
	 * @param number $id
	 */
	public static function getEditPage($id) {
		$app = \Slim\Slim::getInstance();
		$dao = new GroupDAO();
		$userDAO = new UserDAO();

		$app->expires(time());
		
		$item = $dao->fetch($id);
		if (empty($item)) {
			return $app->notFound();
		}

        $numbers = $dao->getNumbers($item->getId());

        $pager1 = new Pager(MAINURL.'/groups/edit/'.$item->getId(), 50);
        $pager1->setQueryMode('page1');
        $pager1->setPage($_GET['page1']);

        $pager2 = new Pager(MAINURL.'/groups/edit/'.$item->getId(), 50);
        $pager2->setQueryMode('page2');
        $pager2->setPage($_GET['page2']);

        $totalRegistered = 0;
        $totalUnregistered = 0;

        $numbersRegistered = array();
        $numbersUnregistered = array();

        $start1 = $pager1->getPageSize() * $pager1->getPage();
        $end1 = $start1 + $pager1->getPageSize();
        $start2 = $pager2->getPageSize() * $pager2->getPage();
        $end2 = $start2 + $pager2->getPageSize();

        $cntRegistered = 0;
        $cntUnregistered = 0;
        foreach ($numbers as $k => $v) {
            if (!empty($v['synced'])) {
                if ($cntRegistered >= $start1 && $cntRegistered < $end1) {
                    $numbersRegistered[$k] = $v;
                }
                $cntRegistered++;
                $totalRegistered++;
            } else {
                if ($cntUnregistered >= $start2 && $cntUnregistered < $end2) {
                    $numbersUnregistered[$k] = $v;
                }
                $cntUnregistered++;
                $totalUnregistered++;
            }
        }

        $pager1->setTotal($totalRegistered);
        $pager1->setCount(count($numbersRegistered));

        $pager2->setTotal($totalUnregistered);
        $pager2->setCount(count($numbersUnregistered));


        $senderDAO = new SenderDAO();

        $strong = \Strong\Strong::getInstance();
        $user = $strong->getUser();

        $senders = $senderDAO->getList(array(
            user_id => $user['id']
        ));
        $app->view->set('senders', $senders);

        $app->view->set('menu', 'groups');
		$app->view->set('id', $id);
        $app->view->set('item', $item);
        $app->view->set('pager1', $pager1);
        $app->view->set('pager2', $pager2);
		$app->view->set('users', $userDAO->getList());
        $app->view->set('numbersRegistered', $numbersRegistered);
        $app->view->set('numbersUnregistered', $numbersUnregistered);
		$app->view->set('duplicates', $dao->getDuplicates($item->getId()));
		
		$app->render('groups/edit.twig.html');
	}
	
	/**
	 * Process edit group form, validate, save to DB
	 * 
	 * @param unknown $id
	 * @return boolean
	 */
	public static function postEditPage($id) {
		$app = \Slim\Slim::getInstance();
		$dao = new GroupDAO();
		$userDAO = new UserDAO();
		
		$item = $dao->fetch($id);
		if (empty($item)) {
			return $app->notFound();
		}
		
		$item->setNickname($_POST['nickname']);

		if ($userDAO->hasRole('ADMIN')) {
			$item->setUserId($_POST['user_id']);
		} else {
			if (empty($id)) {
				$strong = \Strong\Strong::getInstance();
				$user = $strong->getUser();
				$item->setUserId($user['id']);
			}
		}

		$validator = new \Valitron\Validator($_POST);
		$validator->rule('required', 'nickname');

		if($validator->validate()) {
			$item = $dao->save($item);
            if (empty($id)) {
                $app->flash('info', 'Group '.$item->getNickname().' has been created successfully');
            }

            if (!empty($_POST['force_sync'])) {
                self::forceSync();
            }

            $app->redirect(MAINURL.'/groups/edit/'.$item->getId());
		} else {
			$app->view->set('menu', 'groups');
			$app->view->set('id', $id);
			$app->view->set('users', $userDAO->getList());
			$app->view->set('item', $item);
			$app->view->set('numbers', $dao->getNumbers($item->getId()));
			$app->view->set('errors', $validator->errors());

			$app->render('groups/edit.twig.html');
		}
	}
	
	/**
	 * Processes file with numbers uploaded for specied group, puts them to DB
	 * 
	 * @param number $groupId
	 */
	public static function postUploadNumber($groupId) {
		$app = \Slim\Slim::getInstance();
		$groupDAO = new GroupDAO();
		
		if (!empty($_FILES['file'])) {
			$app->response->headers->set('Content-type', 'text/plain');
			
			$numbers = file_get_contents($_FILES['file']['tmp_name']);
			$numbers = explode("\n", $numbers);
			$toImport = array();
			foreach ($numbers as $number) {
				$idx = strpos($number, ',');
				$idx2 = strpos($number, ';');
				if (($idx2 !== false && $idx2 < $idx) || ($idx === false)) {
					$idx = $idx2;
				}
				if ($idx == 0) {
					$idx = strlen($number);
				}
				$msisdn = substr($number, 0, $idx);
				$msisdn = preg_replace('![^0-9]*!', '', $msisdn);
				$nickname = trim(substr($number, $idx+1));
				
				if (empty($msisdn)) continue;
				echo "$msisdn, $nickname\n";

				$toImport[$msisdn] = $nickname;
			}

            $group = $groupDAO->fetch($groupId);
			$groupDAO->addNumbers($group->getUserId(), $groupId, $toImport);

            self::forceSync();
        }
	}
	
	/**
	 * Deletes number for group, MSISDN is specified in $_GET['number'] 
	 * 
	 * @param number $id
	 */
	public static function getDeleteNumber($id) {
		$app = \Slim\Slim::getInstance();
		$dao = new GroupDAO();
		
		$target = $app->request->get('number');
		$dao->deleteNumber($id, $target);
		
		$app->redirect(MAINURL.'/groups/edit/'.$id);
	}

	/**
	 * Show delete confirmation
	 */
	public static function deletePage($id) {
		$app = \Slim\Slim::getInstance();
		$userDAO = new UserDAO();
		if (!$userDAO->hasRole('ADMIN')) {
			return $app->status(403);
		}
		
		$app->view->set('msg', 'It will delete group, its messages and numbers.');
		$app->render('confirm_delete.twig.html');
	}
	
	/**
	 * Deletes group
	 */
	public static function postDeletePage($id) {
		$app = \Slim\Slim::getInstance();
		$userDAO = new UserDAO();
		if (!$userDAO->hasRole('ADMIN')) {
			return $app->status(403);
		}
		
		if (!empty($_POST['yes'])) {
			$dao = new GroupDAO();
			$dao->delete($id);
		}
		
 		return $app->redirect(MAINURL.'/groups');
	}

    public static function forceSync()
    {
        $senderDAO = new SenderDAO();

        $strong = \Strong\Strong::getInstance();
        $user = $strong->getUser();
        $sendersQuery = array();
        $sendersQuery['user_id'] = $user['id'];

        $senders = $senderDAO->getList($sendersQuery);
        foreach ($senders['list'] as $sender) {
            $sender = $senderDAO->fetch($sender->getId());
            if (empty($sender)) {
                continue;
            }

            $flags = $sender->getFlags();
            $flags |= Sender::FLAG_UNSYNC;
            $sender->setFlags($flags);

            $senderDAO->save($sender);
        }
    }

}
