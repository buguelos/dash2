<?php

namespace GitGis\Whatsapp\Model;

use \GitGis\Whatsapp\Model\DBConnection;
use \GitGis\Whatsapp\Model\Group;

/**
 * Group management class
 */
class GroupDAO {

	/**
	 * Get list of groups based on query
	 * 
	 * Params could contain:
	 * - start
	 * - limit
	 * - search - fetches only groups containing specified MSISDN number
	 * - username - fetches only specific group
	 * 
	 * @param unknown $params
	 * @return multitype:Ambigous <number, unknown> number multitype:\GitGis\Whatsapp\Model\Group  mixed
	 */
	public function getList($params = array()) {
		if (empty($params['start'])) $params['start'] = 0;
		if (empty($params['limit'])) $params['limit'] = 500;

		$db = DBConnection::getInstance();
		$list = array();

        $params['searchNumeric'] = preg_replace('![^0-9]*!', '', $params['search']);

		$where = " WHERE (1=1) ";
		if (!empty($params['searchNumeric'])) {
			$where .= " AND (EXISTS (SELECT target FROM numbers WHERE group_id=groups.id
			    AND target=:searchNumeric)
			    OR groups.nickname LIKE '%'||:searchNumeric||'%'
			    ) ";
		} else
        if (!empty($params['search'])) {
            $where .= " AND groups.nickname LIKE CONCAT('%', :search, '%') ";
        }
		if (!empty($params['user_id'])) {
			$where .= ' AND user_id=:user_id ';
		}
		
		$sql = "SELECT COUNT(id) as total FROM groups ".$where;
		$query = $db->prepare($sql);
        if (!empty($params['search'])) {
            $query->bindParam('search', $params['search']);
        }
        if (!empty($params['searchNumeric'])) {
            $query->bindParam('searchNumeric', $params['searchNumeric']);
        }
		if (!empty($params['user_id'])) {
			$query->bindParam('user_id', $params['user_id']);
		}
		$query->execute();
		$row = $query->fetch();
		$total = $row['total'];
		
		$sql = "SELECT * FROM groups ".$where." ORDER BY nickname LIMIT :start, :limit ";
		$query = $db->prepare($sql);
		$query->bindParam('start', $params['start'], \PDO::PARAM_INT);
		$query->bindParam('limit', $params['limit'], \PDO::PARAM_INT);
        if (!empty($params['search'])) {
            $query->bindParam('search', $params['search']);
        }
        if (!empty($params['searchNumeric'])) {
            $query->bindParam('searchNumeric', $params['searchNumeric']);
        }
		if (!empty($params['user_id'])) {
			$query->bindParam('user_id', $params['user_id']);
		}
		$query->execute();
		while ($row = $query->fetch()) {
			$item = new Group();
			$item->setId($row['id']);
			$item->setNickname($row['nickname']);
			$item->setUserId($row['user_id']);

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
	 * Fetches specified group
	 * 
	 * @param number $id
	 * @return \GitGis\Whatsapp\Model\Group
	 */
	public function fetch($id) {
		$db = DBConnection::getInstance();
        $item = new Group();

		if (empty($id)) {
			return $item;
		}
		
		$query = $db->prepare("SELECT * FROM groups WHERE id=:id ");
		$query->bindParam('id', $id);
		$query->execute();
		$row = $query->fetch();
		if (!empty($row)) {
			$item->setId($row['id']);
			$item->setNickname($row['nickname']);
			$item->setUserId($row['user_id']);
		}
		
		return $item;
	}

	/**
	 * Saves group to DB
	 * 
	 * @param Group $item
	 * @throws \Exception
	 * @return Group
	 */
	public function save(Group $item) {
		if (empty($item)) {
			throw new \Exception('Empty item');
		}
		
		$db = DBConnection::getInstance();
		if ($item->getId() == 0) {
			$query = $db->prepare("INSERT INTO groups 
					(nickname, user_id)
					VALUES 
					(:nickname, :user_id)");
			$query->bindParam('nickname', $item->getNickname());
			$query->bindParam('user_id', $item->getUserId());
			$query->execute();
			$item->setId($db->lastInsertId());
		} else {
			$sql = ' UPDATE groups SET
			    nickname = :nickname,
			    user_id = :user_id
			    WHERE id = :id ';
				
			$query = $db->prepare($sql);
			$query->bindParam('id', $item->getId(), \PDO::PARAM_INT);
			$query->bindParam('nickname', $item->getNickname());
			$query->bindParam('user_id', $item->getUserId());
			$query->execute();
				
		}

        return $item;
	}
	
	/**
	 * Deletes group
	 * 
	 * @param number $id
	 */
	public function delete($id) {
		$group = $this->fetch($id);
		
		$db = DBConnection::getInstance();

		$query = $db->prepare("DELETE FROM messages WHERE group_id = :group_id");
		$query->bindParam('group_id', $id, \PDO::PARAM_INT);
		$query->execute();
		
		$query = $db->prepare("DELETE FROM numbers WHERE group_id = :group_id");
		$query->bindParam('group_id', $id, \PDO::PARAM_INT);
		$query->execute();

		$query = $db->prepare("DELETE FROM groups WHERE id = :group_id");
		$query->bindParam('group_id', $id, \PDO::PARAM_INT);
		$query->execute();
	}
	
	/**
	 * Adds numbers to specified group id
	 * 
	 * @param number $userId
     * @param number $groupId
	 * @param array $toImport
	 */
	public function addNumbers($userId, $groupId, $toImport = array()) {
		if (!empty($toImport)) {
			$db = DBConnection::getInstance();
			$query = $db->prepare("REPLACE INTO numbers (group_id, target, nickname) VALUES (:group_id, :target, :nickname) ");
			
			foreach ($toImport as $target => $nickname) {
				$query->bindParam('group_id', $groupId, \PDO::PARAM_INT);
				$query->bindParam('target', $target);
				$query->bindParam('nickname', $nickname);
				$query->execute();
			}
		}
	}

	/**
	 * Removes number from group
	 * 
	 * @param number $id
	 * @param string $target
	 */
	public function deleteNumber($id, $target) {
		$db = DBConnection::getInstance();
		$query = $db->prepare("DELETE FROM numbers WHERE group_id = :group_id AND target = :target ");
		$query->bindParam('group_id', $id, \PDO::PARAM_INT);
		$query->bindParam('target', $target);
		
		$query->execute();
	}
	
	/**
	 * Get numbers for specified group id
	 * 
	 * @param number $id
     * @param bool $synced
     * @return array
	 */

    public function getNumbers($id, $synced = false) {
		$db = DBConnection::getInstance();
		
		$numbers = array();

        $sql = "SELECT * FROM numbers WHERE group_id=:id ORDER BY target ";
        if ($synced) {
            $sql = "SELECT * FROM numbers WHERE group_id=:id AND synced=1 ORDER BY target ";
        }
		$query = $db->prepare($sql);
		$query->bindParam('id', $id);
		$query->execute();
		while ($row = $query->fetch()) {
			$numbers[$row['target']] = $row;
		}
	
		return $numbers;
	}

	/**
	 * Checks if in specified group are number duplicated by other groups
	 * 
	 * @param number $groupId
	 * @return multitype:mixed
	 */
	public function getDuplicates($groupId) {
		$db = DBConnection::getInstance();
	
		$numbers = array();
	
		$query = $db->prepare("SELECT numbers.target, 
				(SELECT COUNT(groups.id) FROM groups JOIN numbers n2 ON (n2.group_id=groups.id) WHERE n2.target=numbers.target) AS count
				FROM numbers
				WHERE numbers.group_id=:id
				ORDER BY numbers.target ");
		$query->bindParam('id', $groupId);
		$query->execute();
		while ($row = $query->fetch()) {
			$numbers[$row['target']] = $row['count'];
		}
	
		return $numbers;
	}

    public function clearSynced($groupId) {
        return;
        $db = DBConnection::getInstance();

        $query = $db->prepare("UPDATE numbers SET synced=0 WHERE group_id = ".$groupId." ");
        $query->execute();

    }

    public function markSynced($groupId, $numbers) {
        $db = DBConnection::getInstance();

        if (empty($numbers)) return;

        if (!is_array($numbers)) $numbers = array($numbers);

        $ids = '';
		foreach ($numbers as $number) {
			$ids .= "'".$number."',";
		}
		$ids = substr($ids, 0, -1);

		$query = $db->prepare("UPDATE numbers SET synced=1 WHERE group_id = ".$groupId." AND target IN (".$ids.") ");
		$query->execute();
	}
	
}
