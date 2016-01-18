<?php

namespace GitGis\Whatsapp\Model;

use \GitGis\Whatsapp\Model\DBConnection;
use \GitGis\Whatsapp\Model\Chat;

/**
 * Inbox management class
 *
 */
class ChatDAO {
	
	/**
	 * Get list of messages based on query
	 *
	 * Params could contain:
	 * - start
	 * - limit
	 * - from - fetches only messages from
	 *
	 * @param array $params
	 * @return multitype:Ambigous <number, unknown> number multitype:\GitGis\Whatsapp\Model\Message  mixed
	 */
	public function getList($params = array()) {
		if (empty($params['start'])) $params['start'] = 0;
		if (empty($params['limit'])) $params['limit'] = 100;
	
		$db = DBConnection::getInstance();
		$list = array();
	
		$where = " WHERE (1=1) ";
        if (!empty($params['from'])) {
            $where .= " AND `from` = :from ";
        }
        if (!empty($params['to'])) {
            $where .= " AND `to` = :to ";
        }
		if (!empty($params['user_id'])) {
			$where .= " AND EXISTS (SELECT * FROM senders WHERE senders.username=chat.to AND senders.`user_id` = :user_id) ";
		}

		$sql = "SELECT COUNT(chat.id) as total FROM chat ".$where;
		$query = $db->prepare($sql);
		if (!empty($params['from'])) {
			$query->bindParam('from', $params['from']);
		}
        if (!empty($params['to'])) {
            $query->bindParam('to', $params['to']);
        }
		if (!empty($params['user_id'])) {
			$query->bindParam('user_id', $params['user_id']);
		}
		$query->execute();
		$row = $query->fetch();
		$total = $row['total'];
	
		$sql = "SELECT chat.*
				FROM chat ".$where." ORDER BY ctime DESC LIMIT :start, :limit ";
		$query = $db->prepare($sql);
		$query->bindParam('start', $params['start'], \PDO::PARAM_INT);
		$query->bindParam('limit', $params['limit'], \PDO::PARAM_INT);
        if (!empty($params['from'])) {
            $query->bindParam('from', $params['from']);
        }
        if (!empty($params['to'])) {
            $query->bindParam('to', $params['to']);
        }
		if (!empty($params['user_id'])) {
			$query->bindParam('user_id', $params['user_id']);
		}
		$query->execute();
		while ($row = $query->fetch()) {
			$item = new Chat();
			$item->setId($row['id']);
			$item->setData($row['data']);
			$item->setFrom($row['from']);
			$item->setFromNickname($row['from_nickname']);
			$item->setTo($row['to']);
			$item->setToNickname($row['to_nickname']);
            $item->setCtime($row['ctime']);

			$list[] = $item;
		}
	
		return array(
				'start' => !empty($params['start']) ? $params['start'] : 0,
				'limit' => !empty($params['limit']) ? $params['limit'] : 0,
				'total' => $total,
				'list' => $list
		);
	}
	
	/**
	 * Fetches specified message
	 *
	 * @param number $id
	 * @return \GitGis\Whatsapp\Model\Chat>
	 */
	public function fetch($id) {
		$db = DBConnection::getInstance();
		$item = null;
	
		if (empty($id)) {
			$item = new Chat();
			return $item;
		}
	
		$query = $db->prepare("SELECT * FROM chat WHERE id=:id ");
		$query->bindParam('id', $id);
		$query->execute();
		$row = $query->fetch();
		if (!empty($row)) {
			$item = new Chat();
			$item->setId($row['id']);
			$item->setData($row['data']);
			$item->setFrom($row['from']);
			$item->setFromNickname($row['from_nickname']);
			$item->setTo($row['to']);
			$item->setToNickname($row['to_nickname']);
            $item->setCtime($row['ctime']);
		}
	
		return $item;
	}
	
	public function save(Chat $item) {
		if (empty($item)) {
			throw new \Exception('Empty item');
		}

		$db = DBConnection::getInstance();
		
		if ($item->getFromNickname() == '') {
			$sql = "SELECT nickname FROM numbers WHERE nickname<>'' AND target=:target ";
			$query = $db->prepare($sql);
			$query->bindParam('target', $item->getFrom());
			$query->execute();
			$row = $query->fetch();
			if (!empty($row['nickname'])) {
				$nickname = $row['nickname'];
				$item->setFromNickname($nickname);
			}
		}
		if ($item->getToNickname() == '') {
			$sql = "SELECT nickname FROM senders WHERE nickname<>'' AND username=:username ";
			$query = $db->prepare($sql);
			$query->bindParam('username', $item->getTo());
			$query->execute();
			$row = $query->fetch();
			if (!empty($row['nickname'])) {
				$nickname = $row['nickname'];
				$item->setToNickname($nickname);
			}
		}
		
		if ($item->getId() == 0) {
            $query = $db->prepare("SELECT * FROM chat WHERE whatsapp_id=:whatsapp_id ");
            $query->bindParam('whatsapp_id', $item->getWhatsappId() );
            $query->execute();
            $row = $query->fetch();
            if (!empty($row)) {
                $item = new Chat();
                $item->setId($row['id']);
                $item->setData($row['data']);
                $item->setFrom($row['from']);
                $item->setFromNickname($row['from_nickname']);
                $item->setTo($row['to']);
                $item->setToNickname($row['to_nickname']);
                $item->setCtime($row['ctime']);
                $item->setWhatsappId($row['whatsapp_id']);
                return $item;
            }

			$query = $db->prepare("INSERT INTO chat
					(`from`, `to`, from_nickname, to_nickname, data, ctime, whatsapp_id)
					VALUES
					(:from, :to, :from_nickname, :to_nickname, :data, :ctime, :whatsapp_id)");
			$query->bindParam('from', $item->getFrom());
			$query->bindParam('to', $item->getTo());
			$query->bindParam('from_nickname', $item->getFromNickname());
			$query->bindParam('to_nickname', $item->getToNickname());
			$query->bindParam('data', $item->getData());
			$query->bindParam('ctime', $item->getCtime(), \PDO::PARAM_INT);
			$query->bindParam('whatsapp_id', $item->getWhatsappId() );
			$query->execute();
			$item->setId($db->lastInsertId());
		} else {
			$sql = ' UPDATE chat SET ';
			$sql.= ' whatsapp_id = :whatsapp_id, ';
			$sql.= ' `from` = :from, ';
			$sql.= ' `to` = :to, ';
			$sql.= ' from_nickname = :from_nickname, ';
			$sql.= ' to_nickname= :to_nickname, ';
			$sql.= ' data = :data, ';
			$sql.= ' ctime = :ctime ';
			$sql.= ' WHERE id = :id ';
	
			$query = $db->prepare($sql);
			$query->bindParam('id', $item->getId(), \PDO::PARAM_INT);
			$query->bindParam('from', $item->getFrom());
			$query->bindParam('to', $item->getTo());
			$query->bindParam('from_nickname', $item->getFromNickname());
			$query->bindParam('to_nickname', $item->getToNickname());
			$query->bindParam('data', $item->getData());
			$query->bindParam('ctime', $item->getCtime());
			$query->bindParam('whatsapp_id', $item->getWhatsappId() );
			$query->execute();
	
		}
	
		return $item;
	}

	/**
	 * Deletes message from DB
	 *
	 * @param number $id
	 */
	public function delete($id) {
		$db = DBConnection::getInstance();
	
		$query = $db->prepare("DELETE FROM chat WHERE id=:message_id ");
		$query->bindParam('message_id', $id);
		$query->execute();
	}
}
