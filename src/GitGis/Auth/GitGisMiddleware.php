<?php
namespace GitGis\Auth;

use \Slim\Middleware;

class GitGisMiddleware extends Middleware {
	public function call() {
		$app = $this->app;
		
		if ($app->view instanceof \Slim\Views\Twig) {
			$strong = \Strong\Strong::getInstance();
			$user = $strong->getUser();

			$userDAO = new \GitGis\Whatsapp\Model\UserDAO();
			$user = $userDAO->fetch($user['id']);
			
			$app->view->set('user', $user);
			
			$twig = $app->view->getInstance();
			$twig->addFunction(new \Twig_SimpleFunction('hasRole', function ($role) use ($user) {
				return in_array($role, explode(',', $user->getRoles()));
			}));
		}

		$this->next->call();
	}
}
