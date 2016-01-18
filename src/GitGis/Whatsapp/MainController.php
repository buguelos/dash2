<?php

namespace GitGis\Whatsapp;

/**
 * Main controller
 */
class MainController {

	/**
	 * Redirects to /messages link
	 */
	public static function getPage() {
		$app = \Slim\Slim::getInstance();
		$app->redirect(MAINURL.'/messages');
	}
	
}
