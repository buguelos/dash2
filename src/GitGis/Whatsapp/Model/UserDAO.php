<?php

namespace GitGis\Whatsapp\Model;

use \GitGis\Whatsapp\Model\DBConnection;
use \GitGis\Whatsapp\Model\User;

/**
 * Authentication/user management class
 */
class UserDAO {

	/**
	 * Get list of users based on query
	 * 
	 * Params could contain:
	 * - start
	 * - limit
	 * - username - fetches only specific user
	 * 
	 * @param array $params
	 * @return multitype:Ambigous <number, unknown> number multitype:\GitGis\Whatsapp\Model\User  mixed
	 */
	public function getList($params = array()) {
		if (empty($params['start'])) $params['start'] = 0;
		if (empty($params['limit'])) $params['limit'] = 100;

		$db = DBConnection::getInstance();
		$list = array();
		
		$where = ' WHERE (1=1) ';
        if (!empty($params['search'])) {
            $where .= " AND username like CONCAT ('%', :search, '%') ";
        }
		if (!empty($params['username'])) {
			$where .= ' AND username=:username ';
		}
		if (isset($params['deleted'])) {
			if ($params['deleted'] > 0) {
				$where .= ' AND dtime > 0 ';
			} else {
				$where .= ' AND NOT dtime > 0 ';
			}
		}

		$sql = "SELECT COUNT(id) as total FROM users ".$where;
		$query = $db->prepare($sql);
		if (!empty($params['username'])) {
			$query->bindParam('username', $params['username']);
		}
        if (!empty($params['search'])) {
            $query->bindParam('search', $params['search']);
        }
		$query->execute();
		$row = $query->fetch();
		$total = $row['total'];
		
		$sql = "SELECT * FROM users ".$where." ORDER BY username LIMIT :start, :limit ";
		$query = $db->prepare($sql);
		$query->bindParam('start', $params['start'], \PDO::PARAM_INT);
		$query->bindParam('limit', $params['limit'], \PDO::PARAM_INT);
		if (!empty($params['username'])) {
			$query->bindParam('username', $params['username']);
		}
        if (!empty($params['search'])) {
            $query->bindParam('search', $params['search']);
        }
		$query->execute();
		while ($row = $query->fetch()) {
			$item = new User();
			$item->setId($row['id']);
			$item->setUsername($row['username']);
			$item->setPassword('');
			$item->setRoles($row['roles']);
			$item->setCredits((int) $row['credits']);
			$item->setCtime($row['ctime']);
			$item->setDtime($row['dtime']);

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
	 * Fetches user with specific id
	 * 
	 * @param number $id
	 * @return \GitGis\Whatsapp\Model\User|Ambigous <NULL, \GitGis\Whatsapp\Model\User>
	 */
	public function fetch($id) {
		$db = DBConnection::getInstance();
		$item = null;
		
		if (empty($id)) {
			$item = new User();
			return $item;
		}
		
		$query = $db->prepare("SELECT * FROM users WHERE id=:id ");
		$query->bindParam('id', $id);
		$query->execute();
		$row = $query->fetch();
		if (!empty($row)) {
			$item = new User();
			$item->setId($row['id']);
			$item->setUsername($row['username']);
			$item->setPassword('');
			$item->setRoles($row['roles']);
			$item->setCredits((int) $row['credits']);
			$item->setCtime($row['ctime']);
			$item->setDtime($row['dtime']);
		}
		
		return $item;	
	}

	/**
	 * Stores user into db
	 * 
	 * @param User $item
	 * @throws \Exception
	 * @return unknown
	 */
	public function save(User $item) {
		if (empty($item)) {
			throw new \Exception('Empty item');
		}
		
		if ($item->getCtime() == 0) {
			$item->setCtime(time());
		}
		
		$db = DBConnection::getInstance();
		if ($item->getId() == 0) {
			$query = $db->prepare("INSERT INTO users
					(username, password, roles, credits, ctime, dtime)
					VALUES
					(:username, :password, :roles, :credits, :ctime, :dtime)");
			$query->bindParam('username', $item->getUsername());
			$query->bindParam('password', $item->getPassword());
			$query->bindParam('roles', $item->getRoles());
			$query->bindParam('credits', $item->getCredits());
			$query->bindParam('ctime', $item->getCtime());
			$query->bindParam('dtime', $item->getDtime());
			$query->execute();
			$item->setId($db->lastInsertId());
		} else {
			$sql = ' UPDATE users SET ';
			if ($item->getPassword() != '') $sql.= ' password = :password, ';
			if ($item->getCredits() !== null) $sql.= ' credits = :credits, ';
			$sql.= ' roles = :roles, ';
			$sql.= ' ctime = :ctime, ';
			$sql.= ' dtime = :dtime, ';
			$sql.= ' username = :username ';
			$sql.= ' WHERE id = :id ';

			$query = $db->prepare($sql);
			$query->bindParam('id', $item->getId(), \PDO::PARAM_INT);
			$query->bindParam('username', $item->getUsername());
			if ($item->getPassword() != '') {
				$query->bindParam('password', $item->getPassword());
			}
			if ($item->getCredits() !== null) {
				$query->bindParam('credits', $item->getCredits());
			}
			$query->bindParam('roles', $item->getRoles());
			$query->bindParam('ctime', $item->getCtime());
			$query->bindParam('dtime', $item->getDtime());
			$query->execute();
		
		}
		
		return $item;	
	}
	
	/**
	 * Deletes user
	 * 
	 */
	public function delete($id) {
		$db = DBConnection::getInstance();
		
		$sql = " UPDATE users SET password='', dtime=:dtime WHERE id=:id ";
		
		$query = $db->prepare($sql);
		$query->bindParam('id', $id, \PDO::PARAM_INT);
		$query->bindParam('dtime', time());
		$query->execute();
		
	}
	
	public function hasRole($role, User $user = null) {
		if (empty($user)) {
			$strong = \Strong\Strong::getInstance();
			$user = $strong->getUser();
		}
		
		if (is_array($user)) {
			$user = $this->fetch($user['id']);
		}

		return in_array($role, explode(',', $user->getRoles()));
	}
	
	
}
