<?php

namespace GitGis\Whatsapp\Model;
/**
 * Inbox message
 *
 */
class Chat {

	/**
	 * Id of message
	 *
	 * @var unknown
	 */
	private $id;

	/**
	 * Text of message or link to audio/photo/video file
	 *
	 * @var string
	 */
	private $data;

	/**
	 * Creation timestamp
	 *
	 * @var number
	 */
	private $ctime;

	/**
	 * From MSISDN
	 *
	 * @var string
	 */
	private $from;

	/**
	 * From nickname
	 *
	 * @var string
	 */
	private $from_nickname;

	/**
	 * To MSISDN
	 *
	 * @var string
	 */
	private $to;

	/**
	 * To nickname
	 *
	 * @var string
	 */
	private $to_nickname;

	/**
	 * Id of Whatsapp message
	 *
	 * @var string
	 */
	private $whatsapp_id;

	/**
	 * Id of message
	 */
	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	/**
	 * Text of message or link to audio/photo/video file
	 */
	public function getData() {
		return $this->data;
	}

	public function setData($data) {
		$this->data = $data;
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
	 * From MSISDN
	 *
	 * @var string
	 */
	public function getFrom() {
		return $this->from;
	}

	public function setFrom($from) {
		$this->from = $from;
	}

	/**
	 * From nickname
	 *
	 * @var string
	 */
	public function getFromNickname() {
		return $this->from_nickname;
	}

	public function setFromNickname($from_nickname) {
		$this->from_nickname = $from_nickname;
	}

	/**
	 * To MSISDN
	 *
	 * @var string
	 */
	public function getTo() {
		return $this->to;
	}

	public function setTo($to) {
		$this->to = $to;
	}

	/**
	 * To nickname
	 *
	 * @var string
	 */
	public function getToNickname() {
		return $this->to_nickname;
	}

	public function setToNickname($to_nickname) {
		$this->to_nickname = $to_nickname;
	}

	/**
	 * Id of Whatsapp message
	 *
	 * @var string
	 */
	public function getWhatsappId() {
		return $this->whatsapp_id;
	}

	public function setWhatsappId($whatsapp_id) {
		$this->whatsapp_id = $whatsapp_id;
	}

}
