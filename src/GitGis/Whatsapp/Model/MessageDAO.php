<?php

namespace GitGis\Whatsapp\Model;

use \GitGis\Whatsapp\Model\DBConnection;
use \GitGis\Whatsapp\Model\Message;
use \GitGis\Whatsapp\Model\GroupDAO;

/**
 * Message management class
 *
 */
class MessageDAO {

	/**
	 * Get list of messages based on query
	 * 
	 * Params could contain:
	 * - start
	 * - limit
	 * - toSend - gets only messages scheduled after specified timestamp 
	 * - username - fetches only specific message
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
		if (!empty($params['toSend'])) {
            $ids = array(0 => 0);

            $sql = " SELECT messages.id AS message_id FROM messages
                LEFT JOIN message_targets ON (messages.id=message_targets.message_id)
 				WHERE
 				    messages.stime < ".(intval($params['toSend']))."
				GROUP BY messages.id
				HAVING COUNT(message_targets.status) = 0 ";
            $query = $db->prepare($sql);
            $query->execute();
            while ($row = $query->fetch()) {
                $ids[$row['message_id']] = $row['message_id'];
            }

            $sql = " SELECT messages.id AS message_id FROM messages
                LEFT JOIN message_targets ON (messages.id=message_targets.message_id)
 				WHERE
 				    message_targets.status = 0 AND
 				    messages.stime < ".(intval($params['toSend']))."
				GROUP BY messages.id
				HAVING COUNT(message_targets.status) > 0 ";
            $query = $db->prepare($sql);
            $query->execute();
            while ($row = $query->fetch()) {
                $ids[$row['message_id']] = $row['message_id'];
            }

            $where .= " AND id IN (".implode(',', array_values($ids)).") ";
		}
		if (!empty($params['user_id'])) {
			$where .= " AND EXISTS (SELECT * FROM groups WHERE messages.group_id=groups.id AND groups.user_id=:user_id) ";
		}
        if (!empty($params['start_date'])) {
            $where .= " AND messages.ctime >= :start_date ";
        }
        if (!empty($params['end_date'])) {
            $where .= " AND messages.ctime <= :end_date ";
        }

		$sql = "SELECT COUNT(messages.id) as total FROM messages ".$where;
		$query = $db->prepare($sql);
		if (!empty($params['user_id'])) {
			$query->bindParam('user_id', $params['user_id']);
		}
        if (!empty($params['start_date'])) {
            $query->bindParam('start_date', $params['start_date']);
        }
        if (!empty($params['end_date'])) {
            $query->bindParam('end_date', $params['end_date']);
        }
		$query->execute();
		$row = $query->fetch();
		$total = $row['total'];
		
		$sql = "SELECT messages.*, 
				(SELECT max(statuses.status) FROM statuses WHERE statuses.message_id = messages.id ) as max_status 
				FROM messages ".$where." ORDER BY ctime DESC LIMIT :start, :limit ";
		$query = $db->prepare($sql);
		$query->bindParam('start', $params['start'], \PDO::PARAM_INT);
		$query->bindParam('limit', $params['limit'], \PDO::PARAM_INT);
		if (!empty($params['user_id'])) {
			$query->bindParam('user_id', $params['user_id']);
		}
        if (!empty($params['start_date'])) {
            $query->bindParam('start_date', $params['start_date']);
        }
        if (!empty($params['end_date'])) {
            $query->bindParam('end_date', $params['end_date']);
        }
        $query->execute();
		while ($row = $query->fetch()) {
			$item = new Message();
			$item->setId($row['id']);
            $item->setGroupId($row['group_id']);
            $item->setSenderId($row['sender_id']);
			$item->setUserId($row['user_id']);
			$item->setkind($row['kind']);
			$item->setTarget($row['target']);
			$item->setData($row['data']);
			$item->setCtime($row['ctime']);
			$item->setStime($row['stime']);
			$item->max_status = $row['max_status'];

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
	 * @return \GitGis\Whatsapp\Model\Message>
	 */
	public function fetch($id) {
		$db = DBConnection::getInstance();
		$item = null;
		
		if (empty($id)) {
			return new Message();
		}
		
		$query = $db->prepare("SELECT * FROM messages WHERE id=:id ");
		$query->bindParam('id', $id);
		$query->execute();
		$row = $query->fetch();
		if (!empty($row)) {
			$item = new Message();
			$item->setId($row['id']);
			$item->setGroupId($row['group_id']);
            $item->setSenderId($row['sender_id']);
			$item->setUserId($row['user_id']);
			$item->setkind($row['kind']);
			$item->setTarget($row['target']);
			$item->setData($row['data']);
			$item->setCtime($row['ctime']);
			$item->setStime($row['stime']);
		}
		
		return $item;
	}
	
	/**
	 * Saves message to DB
	 * 
	 * @param Message $item
	 * @throws \Exception
	 * @return Message
	 */
	public function save(Message $item) {
		if (empty($item)) {
			throw new \Exception('Empty item');
		}
		$groupDao = new GroupDAO();
		$item->setTarget(implode(',', array_keys($groupDao->getNumbers($item->getGroupId()))));
		
// 		if (!$item->getTarget()) $item->setTarget('');
		if (!$item->getData()) $item->setData('');
		if (!$item->getStime()) $item->setStime(0);
		
		$db = DBConnection::getInstance();
		if ($item->getId() == 0) {
			$query = $db->prepare("INSERT INTO messages
					(group_id, sender_id, user_id, kind, target, data, ctime, stime)
					VALUES
					(:group_id, :sender_id, :user_id, :kind, :target, :data, :ctime, :stime)");
            $query->bindParam('group_id', $item->getGroupId());
            $query->bindParam('sender_id', $item->getSenderId());
			$query->bindParam('user_id', $item->getUserId());
			$query->bindParam('kind', $item->getKind());
			$query->bindParam('target', $item->getTarget());
			$query->bindParam('data', $item->getData());
			$query->bindParam('ctime', time() );
			$query->bindParam('stime', $item->getStime());
			$query->execute();
			$item->setId($db->lastInsertId());
		} else {
			$sql = ' UPDATE messages SET
                group_id = :group_id,
                sender_id = :sender_id,
                user_id = :user_id,
                kind = :kind,
			    target = :target,
                data = :data,
			    stime = :stime
                WHERE id = :id ';
		
			$query = $db->prepare($sql);
			$query->bindParam('id', $item->getId(), \PDO::PARAM_INT);
			$query->bindParam('group_id', $item->getGroupId(), \PDO::PARAM_INT);
            $query->bindParam('sender_id', $item->getSenderId(), \PDO::PARAM_INT);
			$query->bindParam('user_id', $item->getUserId(), \PDO::PARAM_INT);
			$query->bindParam('kind', $item->getKind());
			$query->bindParam('target', $item->getTarget());
			$query->bindParam('data', $item->getData());
			$query->bindParam('stime', $item->getStime());
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
		
		$item = $this->fetch($id);
        if (empty($item)) {
            return;
        }

		$statuses = $this->getStatuses($item);
		if (!empty($statuses)) {
			return;
		}
		
		$query = $db->prepare("DELETE FROM statuses WHERE message_id=:message_id ");
		$query->bindParam('message_id', $id);
		$query->execute();

		$query = $db->prepare("DELETE FROM messages WHERE id=:message_id ");
		$query->bindParam('message_id', $id);
		$query->execute();
	}

	/**
	 * Gets statuses for whatsapp_id (id of sent message returned by whatsapp)
	 * 
	 * @param unknown $whatsapp_id
	 * @return multitype:mixed
	 */
	public function getStatusesByWhatsappId($whatsapp_id) {
		$retVal = array();
		$db = DBConnection::getInstance();
		
		$query = $db->prepare("SELECT * FROM statuses WHERE whatsapp_id=:whatsapp_id ORDER BY id DESC ");
		$query->bindParam('whatsapp_id', $whatsapp_id);
		$query->execute();
		while ($row = $query->fetch()) {
			$retVal[$row['id']] = $row;
		}
		
		return $retVal;
	}

    public function getMessageByWhatsappId($whatsapp_id) {
        $db = DBConnection::getInstance();

        $query = $db->prepare("SELECT message_id FROM statuses WHERE whatsapp_id=:whatsapp_id AND message_id <> '' LIMIT 1 ");
        $query->bindParam('whatsapp_id', $whatsapp_id);
        $query->execute();
        if ($row = $query->fetch()) {
            return $this->fetch($row['message_id']);
        }

    }

    /**
	 * Gets statuses for message
	 * 
	 * @param Message $item
	 * @return multitype:mixed
	 */
	public function getStatuses(Message $item) {
		$retVal = array();
		$db = DBConnection::getInstance();
		
		$query = $db->prepare("SELECT * FROM statuses WHERE message_id=:message_id ORDER BY id DESC ");
		$query->bindParam('message_id', $item->getId());
		$query->execute();
		while ($row = $query->fetch()) {
			$retVal[$row['id']] = $row;
		}

		return $retVal;
	}
	
	/**
	 * Add status to message
	 * 
	 * @param Message $item
	 * @param number $status
	 * @param string $debug
	 * @param string $target
	 * @param string $whatsapp_id
	 */
	public function addStatus(Message $item, $status, $debugMsg = '', $target = '', $whatsapp_id = '') {
		$db = DBConnection::getInstance();

		$query = $db->prepare("INSERT INTO statuses
					(message_id, mtime, status, debug, target, whatsapp_id)
					VALUES
					(:message_id, :mtime, :status, :debug, :target, :whatsapp_id)");
		$query->bindParam('message_id', $item->getId());
		$query->bindParam('mtime', time());
		$query->bindParam('status', $status);
		$query->bindParam('debug', $debugMsg);
		$query->bindParam('target', $target);
		$query->bindParam('whatsapp_id', $whatsapp_id);
		$query->execute();
	}

    public function getSentTargetsById($whatsapp_id) {
        $retVal = array();

        $db = DBConnection::getInstance();

        $status = Message::MESSAGE_STATUS_SENT;

        $query = $db->prepare("SELECT DISTINCT target FROM statuses WHERE whatsapp_id=:whatsapp_id AND status=:status");
        $query->bindParam('whatsapp_id', $whatsapp_id);
        $query->bindParam('status', $status);

        $query->execute();

        while ($row = $query->fetch()) {
            $retVal[] = $row['target'];
        }

        return $retVal;
    }

    /**
     * Changes force resend status to resent
     *
     * @param Message $item
     */
    public function clearRetry(Message $item) {
        $db = DBConnection::getInstance();

        $query = $db->prepare("UPDATE statuses
					SET status = 16
					WHERE status = 2 AND message_id = :message_id ");
        $query->bindParam('message_id', $item->getId());
        $query->execute();
    }

    /**
     * Clears custom fields
     *
     * @param Message $item
     */
    public function clearCustomFields(Message $item) {
        $db = DBConnection::getInstance();

        $query = $db->prepare("DELETE FROM custom_message_fields WHERE message_id = :message_id ");
        $query->bindParam('message_id', $item->getId());
        $query->execute();
    }

    /**
     * Adds custom fields to message's target
     *
     * @param Message $item
     * @param $msisdn
     * @param $cells
     */
    public function addCustomFields(Message $item, $msisdn, $cells) {
        if (empty($msisdn)) return;

        $db = DBConnection::getInstance();

        for ($i = 0; $i < 5; $i++) {
            if (empty($cells[$i])) $cells[$i] = '';
        }

        $query = $db->prepare("REPLACE INTO custom_message_fields
            (message_id, target, field1, field2, field3, field4, field5)
            VALUES
            (:message_id, :target, :field1, :field2, :field3, :field4, :field5)
        ");
        $query->bindParam('message_id', $item->getId());
        $query->bindParam('target', $msisdn);
        $query->bindParam('field1', $cells[0]);
        $query->bindParam('field2', $cells[1]);
        $query->bindParam('field3', $cells[2]);
        $query->bindParam('field4', $cells[3]);
        $query->bindParam('field5', $cells[4]);
        $query->execute();
    }

    /**
     * Get array of custom fields
     *
     * @param Message $item
     */
    public function getCustomFields(Message $item) {
        $db = DBConnection::getInstance();

        $query = $db->prepare("SELECT * FROM custom_message_fields WHERE message_id = :message_id ");
        $query->bindParam('message_id', $item->getId());
        $query->execute();

        $retVal = array();

        while ($row = $query->fetch()) {
            $retVal[$row['target']] = array(
                'field1' => $row['field1'],
                'field2' => $row['field2'],
                'field3' => $row['field3'],
                'field4' => $row['field4'],
                'field5' => $row['field5']
            );
        }

        return $retVal;
    }

    /**
     * Return array of target => current status
     *
     * @param Message $message
     * @return array
     */
    public function getMessageTargetStatuses(Message $message) {
        $retVal = array();

        $db = DBConnection::getInstance();

        $query = $db->prepare("SELECT * FROM message_targets WHERE message_id = :message_id ");
        $query->bindParam('message_id', $message->getId());
        $query->execute();

        while ($row = $query->fetch()) {
            $retVal[$row['target']] = $row['status'];
        }

        return $retVal;
    }

    /**
     * Sets status of message's target
     *
     * @param Message $message
     * @param $target
     * @param $status
     */
    public function setMessageTargetStatus(Message $message, $target, $status) {
        $db = DBConnection::getInstance();

        $query = $db->prepare("REPLACE INTO message_targets (message_id, target, status) VALUES (:message_id, :target, :status) ");
        $query->bindParam('message_id', $message->getId());
        $query->bindParam('target', $target);
        $query->bindParam('status', $status);
        $query->execute();
    }

    /**
     * Resends message
     *
     * @param Message $message
     */
    public function resend(Message $message)
    {
        $db = DBConnection::getInstance();

        $query = $db->prepare("UPDATE message_targets SET status = :new_status
            WHERE
                message_id = :message_id AND
                status = :old_status ");

        $oldStatus = Message::MESSAGE_STATUS_SENT;
        $newStatus = 0;

        $query->bindParam('message_id', $message->getId());
        $query->bindParam('old_status', $oldStatus);
        $query->bindParam('new_status', $newStatus);
        $query->execute();
    }

}
