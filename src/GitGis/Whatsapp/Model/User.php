<?php

namespace GitGis\Whatsapp\Model;
/**
 * User entity used during authentication
 *
 */
class User {

	/**
	 * Id of user
	 * 
	 * @var number
	 */
	private $id;

	/**
	 * Username / login
	 * 
	 * @var string
	 */
	private $username;

	/**
	 * Password for authentication stored with MD5
	 * 
	 * @var string
	 */
	private $password;

	/**
	 * Comma separeted list of roles 
	 *
	 * @var string
	 */
	private $roles;

	/**
	 * Credits left
	 *
	 * @var number
	 */
	private $credits = 0;
	
	/**
	 * Creation timestamp
	 *
	 * @var number
	 */
	private $ctime = 0;
	
	/**
	 * Delete timestamp
	 *
	 * @var number
	 */
	private $dtime = 0;

	/**
	 * Id of user
	 */
	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * Username / login
	 */
	public function getUsername() {
		return $this->username;
	}

	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * Password for authentication stored with MD5
	 */
	public function getPassword() {
		return $this->password;
	}

	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * Comma separeted list of roles
	 *
	 * @var string
	 */
	public function getRoles() {
		return $this->roles;
	}

	public function setRoles($roles) {
		$this->roles = $roles;
	}

	/**
	 * Credits left
	 *
	 * @var number
	 */
	public function getCredits() {
		return $this->credits;
	}

	public function setCredits($credits) {
		$this->credits = $credits;
	}

	/**
	 * Creation timestamp
	 */
	public function getCtime() {
		return $this->ctime;
	}
	
	public function setCtime($ctime) {
		$this->ctime = $ctime;
	}
	
	/**
	 * Delete timestamp
	 */
	public function getDtime() {
		return $this->dtime;
	}
	
	public function setDtime($dtime) {
		$this->dtime = $dtime;
	}
}
