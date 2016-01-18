<?php

namespace GitGis\Whatsapp;

use \GitGis\Whatsapp\Model\UserDAO;
use \GitGis\Whatsapp\Model\SenderDAO;
use \GitGis\Whatsapp\Model\WhatsappDAO;
use \GitGis\Pager;

/**
 * Senders controller
 *
 */
class SendersController {

    /**
     * Get list of senders
     *
     * @param number $page
     */
    public static function getPage($page = 0) {
        $app = \Slim\Slim::getInstance();
        $dao = new SenderDAO();
        $userDAO = new UserDAO();

        $app->expires(time());

        $strong = \Strong\Strong::getInstance();
        $userStrong = $strong->getUser();

        $query = $_GET;
        if (!$userDAO->hasRole('ADMIN')) {
            $query['user_id'] = $userStrong['id'];
        }
        $pager = new Pager(MAINURL.'/senders/', 25);
        $pager->setPage($page);
        $query = $pager->getQueryArray($query);
        $list = $dao->getList($query);
        $pager->setCount(count($list['list']));
        if (isset($list['total'])) $pager->setTotal($list['total']);

        $ownerList = $dao->getList(array(
            user_id => $userStrong['id']
        ));

        $app->view->set('menu', 'senders');
        $app->view->set('query', $query);
        $app->view->set('result', $list);
        $app->view->set('user_result', $ownerList);
        $app->view->set('pager', $pager);
        $app->view->set('users', $userDAO->getList());

        $app->render('senders/list.twig.html');
    }

    /**
     * Get edit sender form
     *
     * @param number $id
     */
    public static function getEditPage($id) {
        $app = \Slim\Slim::getInstance();
        $dao = new SenderDAO();
        $userDAO = new UserDAO();

        $app->expires(time());

        $item = $dao->fetch($id);
        if (empty($item)) {
            return $app->notFound();
        }

        $app->view->set('menu', 'senders');
        $app->view->set('id', $id);
        $app->view->set('item', $item);
        $app->view->set('users', $userDAO->getList());

        $app->render('senders/edit.twig.html');
    }

    /**
     * Process edit sender form, validate, save to DB
     *
     * @param unknown $id
     * @return boolean
     */
    public static function postEditPage($id) {
        $app = \Slim\Slim::getInstance();
        $dao = new SenderDAO();
        $userDAO = new UserDAO();

        $item = $dao->fetch($id);
        if (empty($item)) {
            return $app->notFound();
        }

        $_POST['username'] = preg_replace('![^0-9]*!', '', $_POST['username']);
        $item->setNickname($_POST['nickname']);
        if (empty($id)) {
            $item->setUsername($_POST['username']);
        }

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
        $validator->rule('required', 'nickname');
        $validator->rule('required', 'username');
        $validator->label('MSISDN');

        if($validator->validate()) {
            $item = $dao->save($item);
            $app->flash('info', 'Sender '.$item->getNickname().' has been created successfully');
            $app->redirect(MAINURL.'/senders/edit/'.$item->getId());
        } else {
            $app->view->set('menu', 'senders');
            $app->view->set('id', $id);
            $app->view->set('users', $userDAO->getList());
            $app->view->set('item', $item);
            $app->view->set('errors', $validator->errors());

            $app->render('senders/edit.twig.html');
        }
    }

    /**
     * Get confirmation/registration form
     *
     * @param number $id
     */
    public static function getConfirmPage($id) {
        $app = \Slim\Slim::getInstance();
        $senderDAO = new SenderDAO();

        $sender = $senderDAO->fetch($id);
        if (empty($sender) || empty($id)) {
            return $app->notFound();
        }

        $app->view->set('menu', 'senders');
        $app->view->set('id', $id);
        $app->view->set('item', $sender);

        try {
            $wdao = WhatsappDAO::instance($sender);
            $wdao->sendSmsCode();

            if ($sender->getPassword() != '') {
                $app->flash('info', 'Your confirmation code has been successfully verified');
                if (!empty($_GET['redir'])) {
                    $app->redirect($_GET['redir']);
                } else {
                    $app->redirect(MAINURL.'/senders/edit/'.$id);
                }
                return ;
            }

        } catch (\Exception $ex) {
            $app->view->set('errors', array($ex->getMessage()."\n".$wdao->getDebugBuf()));
        }

        $app->render('senders/confirm.twig.html');
    }

    /**
     * Process confirmation SMS code
     *
     * @param number $id
     */
    public static function postConfirmPage($id) {
        $app = \Slim\Slim::getInstance();
        $senderDAO = new SenderDAO();

        $sender = $senderDAO->fetch($id);
        if (empty($sender) || empty($id)) {
            return $app->notFound();
        }

        $smscode = $_POST['smscode'];
        $smscode = preg_replace('![^0-9]*!', '', $smscode);

        $errors = array();
        try {
            $wdao = WhatsappDAO::instance($sender);
            $wdao->confirmSmsCode($smscode);

            $app->flash('info', 'Your confirmation code has been successfully verified');
            $app->redirect(MAINURL.'/senders/edit/'.$id);
        } catch (\Exception $ex) {

            $errors['debug'] = array($ex->getMessage()."\n".$wdao->getDebugBuf());

            $app->view->set('menu', 'senders');
            $app->view->set('id', $id);
            $app->view->set('item', $sender);
            $app->view->set('errors', $errors);

            $app->render('senders/confirm.twig.html');

            return;
        }
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

        $app->view->set('msg', 'It will delete sender and its messages.');
        $app->render('confirm_delete.twig.html');
    }

    /**
     * Deletes sender
     */
    public static function postDeletePage($id) {
        $app = \Slim\Slim::getInstance();
        $userDAO = new UserDAO();
        if (!$userDAO->hasRole('ADMIN')) {
            return $app->status(403);
        }

        if (!empty($_POST['yes'])) {
            $dao = new SenderDAO();
            $dao->delete($id);
        }

        return $app->redirect(MAINURL.'/senders');
    }


}
