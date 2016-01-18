<?php

namespace GitGis\Auth;

/**
 * Authetication controller
 *
 */
class AuthController {

	/**
	 * Shows login form
	 */
	public static function getLoginPage() {
		$app = \Slim\Slim::getInstance();
		$app->render('auth/form.twig.html');
	}
	
	/**
	 * Processes login form
	 * 
	 * Credantials are taken from:
	 * $_POST['username']
	 * $_POST['password']
	 * 
	 */
	public static function postLoginPage() {
		$strong = \Strong\Strong::getInstance();
		$isLogged = $strong->login($_POST['username'], $_POST['password'], !empty($_POST['remember']));
		if (!$isLogged) {
			self::getLoginPage();
		} else {
			$app = \Slim\Slim::getInstance();
			if (MAINURL != '') {
				$app->redirect(MAINURL);
			} else {
				$app->redirect('/');
			}
		}
	}
	
	/**
	 * Logout user and redirects to Main Page
	 */
	public static function getLogoutPage() {
		$strong = \Strong\Strong::getInstance();
		$strong->logout(true);
		$app = \Slim\Slim::getInstance();
		if (MAINURL != '') {
			$app->redirect(MAINURL);
		} else {
			$app->redirect('/');
		}
	}
}
