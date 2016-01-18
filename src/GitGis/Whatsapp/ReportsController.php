<?php
/**
 * Created by PhpStorm.
 * User: gg
 * Date: 1/9/14
 * Time: 12:21 PM
 */

namespace GitGis\Whatsapp;


use GitGis\FormUtils;
use GitGis\Pager;
use GitGis\Whatsapp\Model\ChatDAO;
use GitGis\Whatsapp\Model\GroupDAO;
use GitGis\Whatsapp\Model\Message;
use GitGis\Whatsapp\Model\MessageDAO;
use GitGis\Whatsapp\Model\UserDAO;

class ReportsController {

    public static function getSentPage($page = 0) {
        $app = \Slim\Slim::getInstance();

        $limit = 50;
        if ($_GET['format'] == 'csv') {
            $page = 0;
            $limit = 10000;
        }

        $userDAO = new UserDAO();
        $messageDAO = new MessageDAO();
        $groupDAO = new GroupDAO();

        $groups = $groupDAO->getList();
        $groups = $groups['list'];

        $formUtils = new FormUtils();
        $startTime = $formUtils->toTimestamp($_GET['start_date'].' 00:00');
        $endTime = $formUtils->toTimestamp($_GET['end_date'].' 23:59');

        if ($endTime <= 0) {
            $endTime = time();
        }
        if ($startTime <= 0) {
            $startTime = $endTime - 7*24*3600;
        }

        $app->view->set('start_date', $startTime);
        $app->view->set('end_date', $endTime);


        $query = $_GET;
        $query['start_date'] = $startTime;
        $query['end_date'] = $endTime;
        if (!$userDAO->hasRole('ADMIN')) {
            $strong = \Strong\Strong::getInstance();
            $user = $strong->getUser();
            $query['user_id'] = $user['id'];
        }
        $pager = new Pager(MAINURL.'/reports/sent/?'.http_build_query($_GET), $limit);
        $pager->setPage($page);
        $query = $pager->getQueryArray($query);
        $list = $messageDAO->getList($query);
        $pager->setCount(count($list['list']));
        if (isset($list['total'])) $pager->setTotal($list['total']);

        $reportTable = array();
        foreach ($list['list'] as $message) {
            $statuses = $messageDAO->getStatuses($message);

            foreach ($statuses as $status) {
                if (empty($status['target'])) {
                    continue;
                }
                $key = $status['message_id'];

                if (!empty($reportTable[$key])) {
                    $reportRow = $reportTable[$key];
                } else {
                    $reportRow = array();
                    $group = $groups[$message->getGroupId()];
                    $reportRow['group'] = $group->getNickname();
                    $reportRow['groupCnt'] = count($groupDAO->getNumbers($group->getId()));
                    $reportRow['ctime'] = $message->getCtime();
                    $reportRow['msg'] = $message->getData();
                    $reportRow['targets'] = array();
                    $reportRow['sent'] = array();
                    $reportRow['recv'] = array();
                    $reportRow['error'] = 0;
                    $reportRow['status'] = 0;
                }

                $reportRow['targets'] = $message->getTarget();

                switch ($status['status']) {
                    case Message::MESSAGE_STATUS_ERROR:
                        $reportRow['status'] = 'ERR';
                        break;

                    case Message::MESSAGE_STATUS_RECEIVED_BY_SERVER:
                    case Message::MESSAGE_STATUS_RECEIVED_BY_PHONE:
                        $reportRow['status'] = 'RECV';
                        $reportRow['recv'][$status['target']] = 1;
                        break;

                    case Message::MESSAGE_STATUS_SENT:
                        $reportRow['status'] = 'SENT';
                        $reportRow['sent'][$status['target']] = 1;
                        if ($reportRow['sent_time'] < $status['mtime']) $reportRow['sent_time'] = $status['mtime'];
                        break;
                }

                $reportTable[$key] = $reportRow;
            }
        }

        foreach ($reportTable as $k => $v) {
            $v['sentCnt'] = count($v['sent']);
            $v['recvCnt'] = count($v['recv']);

            $reportTable[$k] = $v;
        }

        $app->view->set('menu', 'reports');
        $app->view->set('reportTable', array_values($reportTable));
        $app->view->set('query', $query);
        $app->view->set('pager', $pager);

        if ($_GET['format'] == 'csv') {
            header("Content-type: text/csv");
            header("Content-disposition: attachment; filename=sent.csv");
            $app->render('reports/sent.twig.csv');
        } else {
            $app->render('reports/sent.twig.html');
        }
    }

    public static function getInboxPage($page = 0) {
        $app = \Slim\Slim::getInstance();

        $limit = 25;
        if ($_GET['format'] == 'csv') {
            $page = 0;
            $limit = 10000;
        }

        $userDAO = new UserDAO();
        $chatDAO = new ChatDAO();

        $formUtils = new FormUtils();
        $startTime = $formUtils->toTimestamp($_GET['start_date'].' 00:00');
        $endTime = $formUtils->toTimestamp($_GET['end_date'].' 23:59');

        if ($endTime <= 0) {
            $endTime = time();
        }
        if ($startTime <= 0) {
            $startTime = $endTime - 7*24*3600;
        }

        $app->view->set('start_date', $startTime);
        $app->view->set('end_date', $endTime);


        $query = $_GET;
        $query['start_date'] = $startTime;
        $query['end_date'] = $endTime;
        if (!$userDAO->hasRole('ADMIN')) {
            $strong = \Strong\Strong::getInstance();
            $user = $strong->getUser();
            $query['user_id'] = $user['id'];
        }
        $pager = new Pager(MAINURL.'/reports/inbox/?'.http_build_query($_GET), $limit);
        $pager->setPage($page);
        $query = $pager->getQueryArray($query);
        $list = $chatDAO->getList($query);
        $pager->setCount(count($list['list']));
        if (isset($list['total'])) $pager->setTotal($list['total']);

        $reportTable = array();
        foreach ($list['list'] as $message) {
            $reportTable[] = $message;
        }

        $app->view->set('menu', 'reports');
        $app->view->set('reportTable', array_values($reportTable));
        $app->view->set('query', $query);
        $app->view->set('pager', $pager);

        if ($_GET['format'] == 'csv') {
            header("Content-type: text/csv");
            header("Content-disposition: attachment; filename=inbox.csv");
            $app->render('reports/inbox.twig.csv');
        } else {
            $app->render('reports/inbox.twig.html');
        }
    }

}
