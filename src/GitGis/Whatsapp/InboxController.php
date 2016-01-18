<?php

namespace GitGis\Whatsapp;

use \GitGis\Whatsapp\Model\Chat;
use \GitGis\Whatsapp\Model\ChatDAO;
use \GitGis\Whatsapp\Model\GroupDAO;
use \GitGis\Whatsapp\Model\UserDAO;
use \GitGis\Pager;

/**
 * Message controller
 */
class InboxController {
	
	/**
	 * Get list of messages
	 */
	public static function getPage($page = 0) {
		$app = \Slim\Slim::getInstance();

		$app->expires(time());
		
		$userDAO = new UserDAO();
		$groupDAO = new GroupDAO();
		$groupsQuery = array();
		if (!$userDAO->hasRole('ADMIN')) {
			$strong = \Strong\Strong::getInstance();
			$user = $strong->getUser();
			$groupsQuery['user_id'] = $user['id'];
		}
		$groups = $groupDAO->getList($groupsQuery);
		if (0 == $groups['total']) {
			return $app->redirect(MAINURL.'/groups');
		}
		
		$chatDAO = new ChatDAO();
		
		$query = $_GET;
		$query['from'] = preg_replace('![^0-9]*!', '', $query['search']);
		if (!$userDAO->hasRole('ADMIN')) {
			$strong = \Strong\Strong::getInstance();
			$user = $strong->getUser();
			$query['user_id'] = $user['id'];
		}
		$pager = new Pager(MAINURL.'/inbox/', 25);
		$pager->setPage($page);
		$query = $pager->getQueryArray($query);
		$list = $chatDAO->getList($query);
		$pager->setCount(count($list['list']));
		if (isset($list['total'])) $pager->setTotal($list['total']);
		
		$app->view->set('menu', 'inbox');
		$app->view->set('query', $query);
		$app->view->set('result', $list);
		$app->view->set('pager', $pager);
		
		$app->render('inbox/list.twig.html');
	}
}
