<?php

namespace GitGis\Whatsapp\Model;
/**
 * Group entity
 */
class Group {

	/**
	 * Id of group
	 * 
	 * @var number
	 */
	private $id;

	/**
	 * Human friendly name of group
	 * 
	 * @var string
	 */
	private $nickname;

	/**
	 * Owner id
	 *
	 * @var number
	 */
	private $user_id;

	/**
	 * Id of group
	 */
	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * Human friendly name of group
	 */
	public function getNickname() {
		return $this->nickname;
	}

	public function setNickname($nickname) {
		$this->nickname = $nickname;
	}

	/**
	 * Owner id
	 *
	 * @var number
	 */
	public function getUserId() {
		return $this->user_id;
	}

	public function setUserId($user_id) {
		$this->user_id = $user_id;
	}

}
