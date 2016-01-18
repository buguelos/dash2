<?php

namespace GitGis\Whatsapp;

use \GitGis\Whatsapp\Model\UserDAO;
use \GitGis\Pager;

/**
 * User management controller
 */
class UsersController {

	/**
	 * Get list of users
	 */
	public static function getPage($page = 0) {
		$app = \Slim\Slim::getInstance();
		$dao = new UserDAO();
		
		if (!$dao->hasRole('ADMIN')) {
			return $app->status(403);
		}
		
		$app->expires(time());
		
		$query = $_GET;
		$query['deleted'] = (int) $query['deleted'];
		$pager = new Pager(MAINURL.'/users/', 25);
		$pager->setPage($page);
		$query = $pager->getQueryArray($query);
		$list = $dao->getList($query);
		$pager->setCount(count($list['list']));
		if (isset($list['total'])) $pager->setTotal($list['total']);
		
		$app->view->set('menu', 'users');
        $app->view->set('query', $query);
		$app->view->set('result', $list);
		$app->view->set('pager', $pager);
		$app->view->set('app', $app);

		$app->render('users/list.twig.html');
	}

	/**
	 * Get user edit form
	 * 
	 * @param number $id
	 */
	public static function getEditPage($id) {
		$app = \Slim\Slim::getInstance();
		$dao = new UserDAO();
		
		if (!$dao->hasRole('ADMIN')) {
			return $app->status(403);
		}

		$app->expires(time());
		
		$item = $dao->fetch($id);
		if (empty($item)) {
			return $app->notFound();
		}
		
		$app->view->set('menu', 'users');
		$app->view->set('id', $id);
		$app->view->set('item', $item);
		$strong = \Strong\Strong::getInstance();
		$user = $strong->getUser();
		$app->view->set('user', $user);
		
		$app->render('users/edit.twig.html');
	}
	
	/**
	 * Process user edit form
	 * 
	 * @param number $id
	 * @return boolean
	 */
	public static function postEditPage($id) {
		$app = \Slim\Slim::getInstance();
		$dao = new UserDAO();
		
		if (!$dao->hasRole('ADMIN')) {
			return $app->status(403);
		}
		
		$item = $dao->fetch($id);
		if (empty($item)) {
			return $app->notFound();
		}
		
		$item->setUsername($_POST['username']);
		if ($_POST['credits'] > 0) {
			$item->setCredits($_POST['credits']);
		}
		if (is_array($_POST['roles'])) {
			$item->setRoles(implode(',', $_POST['roles']));
		} else {
			$item->setRoles('');
		}
		$item->setUsername($_POST['username']);
		if (!empty($_POST['password'])) {
			$item->setPassword(md5($_POST['password']));
		}

		$validator = new \Valitron\Validator($_POST);
		$validator->addRule('repeat', function ($name, $value) {
			if ($value != $_POST['password']) return false;
				
			return true;
		});
		$validator->addRule('unique_username', function ($name, $value) use ($id, $dao) {
			$list = $dao->getList(array('username' => $value));
			if (!empty($list['list'])) {
				foreach ($list['list'] as $item) {
					if ($item->getId() != $id) return false;
				}
			}

			return true;
		}, 'is not unique');
			
		$validator->rule('unique_username', 'username');
		$validator->rule('repeat', 'repeat');
		$validator->label('Password repeat');
		if (empty($id)) {
			$validator->rule('required', 'password');
		}
		$validator->rule('required', 'username');
		$validator->label('Login');
		
		if ($validator->validate()) {
			$item = $dao->save($item);
            if (empty($id)) {
                $app->flash('info', 'Account '.$item->getUsername().' has been created successfully');
            }
			$app->redirect(MAINURL.'/users/edit/'.$item->getId());
		} else {
			$app->view->set('menu', 'users');
			$app->view->set('id', $id);
			$app->view->set('item', $item);
			$app->view->set('errors', $validator->errors());

			$app->render('users/edit.twig.html');
		}
	}

	/**
	 * Deletes message
	 */
	public static function deletePage($id) {
		$app = \Slim\Slim::getInstance();
		$dao = new UserDAO();
		$dao->delete($id);

		return $app->redirect(MAINURL.'/users');
	}
	
}
