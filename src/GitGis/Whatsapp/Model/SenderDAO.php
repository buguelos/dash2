<?php

namespace GitGis\Whatsapp\Model;

use \GitGis\Whatsapp\Model\DBConnection;
use \GitGis\Whatsapp\Model\Sender;

/**
 * Sender management class
 */
class SenderDAO {

    /**
     * Get list of senders based on query
     *
     * Params could contain:
     * - start
     * - limit
     * - search - fetches only senders containing specified MSISDN number
     * - username - fetches only specific sender
     *
     * @param unknown $params
     * @return multitype:Ambigous <number, unknown> number multitype:\GitGis\Whatsapp\Model\Sender  mixed
     */
    public function getList($params = array()) {
        if (empty($params['start'])) $params['start'] = 0;
        if (empty($params['limit'])) $params['limit'] = 500;

        $db = DBConnection::getInstance();
        $list = array();

        $params['searchNumeric'] = preg_replace('![^0-9]*!', '', $params['search']);

        $where = " WHERE (1=1) ";
        if (!empty($params['searchNumeric'])) {
            $where .= " AND (senders.username=:searchNumeric
			    OR senders.nickname LIKE '%'||:searchNumeric||'%'
			    ) ";
        } else
            if (!empty($params['search'])) {
                $where .= " AND senders.nickname LIKE CONCAT('%', :search, '%') ";
            }
        if (!empty($params['username'])) {
            $where .= ' AND username=:username ';
        }
        if (!empty($params['user_id'])) {
            $where .= ' AND user_id=:user_id ';
        }

        $sql = "SELECT COUNT(id) as total FROM senders ".$where;
        $query = $db->prepare($sql);
        if (!empty($params['search'])) {
            $query->bindParam('search', $params['search']);
        }
        if (!empty($params['searchNumeric'])) {
            $query->bindParam('searchNumeric', $params['searchNumeric']);
        }
        if (!empty($params['username'])) {
            $query->bindParam('username', $params['username']);
        }
        if (!empty($params['user_id'])) {
            $query->bindParam('user_id', $params['user_id']);
        }
        $query->execute();
        $row = $query->fetch();
        $total = $row['total'];

        $sql = "SELECT * FROM senders ".$where." ORDER BY username LIMIT :start, :limit ";
        $query = $db->prepare($sql);
        $query->bindParam('start', $params['start'], \PDO::PARAM_INT);
        $query->bindParam('limit', $params['limit'], \PDO::PARAM_INT);
        if (!empty($params['search'])) {
            $query->bindParam('search', $params['search']);
        }
        if (!empty($params['searchNumeric'])) {
            $query->bindParam('searchNumeric', $params['searchNumeric']);
        }
        if (!empty($params['username'])) {
            $query->bindParam('username', $params['username']);
        }
        if (!empty($params['user_id'])) {
            $query->bindParam('user_id', $params['user_id']);
        }
        $query->execute();
        while ($row = $query->fetch()) {
            $item = new Sender();
            $item->setId($row['id']);
            $item->setUsername($row['username']);
            $item->setIdentity($row['identity']);
            $item->setNickname($row['nickname']);
            $item->setPassword($row['password']);
            $item->setUserId($row['user_id']);
            $item->setChallengeData($row['challenge_data']);
            $item->setFlags($row['flags']);

            $list[$row['id']] = $item;
        }

        return array(
            'start' => !empty($params['start']) ? $params['start'] : 0,
            'limit' => !empty($params['limit']) ? $params['limit'] : 0,
            'total' => $total,
            'list' => $list
        );
    }

    /**
     * Fetches specified sender
     *
     * @param number $id
     * @return \GitGis\Whatsapp\Model\Sender
     */
    public function fetch($id) {

        if (empty($id)) {
            return new Sender();
        }

        $item = $this->fetchBySql("SELECT * FROM senders WHERE id=:id ", array(
            'id' => $id
        ));

        if (!empty($item)) {
            return $item;
        }

        return new Sender();
    }

    public function fetchByUserName($userName) {
        $item = $this->fetchBySql("SELECT * FROM senders WHERE username=:username ", array(
            'username' => $userName
        ));

        return $item;
    }

    protected function fetchBySql($sql, $params = array()) {
        $db = DBConnection::getInstance();
        $query = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $query->bindParam($k, $v);
        }
        $query->execute();
        $row = $query->fetch();
        if (!empty($row)) {
            $item = new Sender();
            $item->setId($row['id']);
            $item->setUsername($row['username']);
            $item->setIdentity($row['identity']);
            $item->setNickname($row['nickname']);
            $item->setPassword($row['password']);
            $item->setUserId($row['user_id']);
            $item->setChallengeData($row['challenge_data']);
            $item->setFlags($row['flags']);
            return $item;
        }

        return null;
    }

    /**
     * Saves sender to DB
     *
     * @param Sender $item
     * @throws \Exception
     * @return Sender
     */
    public function save(Sender $item) {
        if (empty($item)) {
            throw new \Exception('Empty item');
        }

        if (!$item->getIdentity()) $item->setIdentity('');
        if (!$item->getPassword()) $item->setPassword('');

        $db = DBConnection::getInstance();
        if ($item->getId() == 0) {
            $query = $db->prepare("INSERT INTO senders
					(username, identity, nickname, password, user_id, challenge_data, flags)
					VALUES 
					(:username, :identity, :nickname, :password, :user_id, :challenge_data, :flags)");
            $query->bindParam('username', $item->getUsername());
            $query->bindParam('nickname', $item->getNickname());
            $query->bindParam('identity', $item->getIdentity());
            $query->bindParam('password', $item->getPassword());
            $query->bindParam('user_id', $item->getUserId());
            $query->bindParam('challenge_data', $item->getChallengeData());
            $query->bindParam('flags', $item->getFlags());
            $query->execute();
            $item->setId($db->lastInsertId());
        } else {
            $sql = ' UPDATE senders SET ';
            if ($item->getIdentity() !== null) $sql.= ' identity = :identity, ';
            if ($item->getPassword() !== null) $sql.= ' password = :password, ';
            $sql.= ' nickname = :nickname, ';
            $sql.= ' username = :username, ';
            $sql.= ' challenge_data = :challenge_data, ';
            $sql.= ' flags = :flags, ';
            $sql.= ' user_id = :user_id ';
            $sql.= ' WHERE id = :id ';

            $query = $db->prepare($sql);
            $query->bindParam('id', $item->getId(), \PDO::PARAM_INT);
            $query->bindParam('username', $item->getUsername());
            $query->bindParam('identity', $item->getIdentity());
            $query->bindParam('nickname', $item->getNickname());
            $query->bindParam('password', $item->getPassword());
            $query->bindParam('user_id', $item->getUserId());
            $query->bindParam('challenge_data', $item->getChallengeData());
            $query->bindParam('flags', $item->getFlags());
            $query->execute();

        }

        return $item;
    }

    /**
     * Deletes sender
     *
     * @param number $id
     */
    public function delete($id) {
        $sender = $this->fetch($id);

        $db = DBConnection::getInstance();

        $query = $db->prepare("DELETE FROM chat WHERE `to` = :to");
        $query->bindParam('to', $sender->getUsername());
        $query->execute();

        $query = $db->prepare("DELETE FROM messages WHERE sender_id = :sender_id");
        $query->bindParam('sender_id', $id, \PDO::PARAM_INT);
        $query->execute();

        $query = $db->prepare("DELETE FROM senders WHERE id = :sender_id");
        $query->bindParam('sender_id', $id, \PDO::PARAM_INT);
        $query->execute();
    }

}
