<?php

namespace GitGis\Whatsapp\Model;

/**
 * Message entity
 *
 */
class Message {

	/**
	 * Text message kind
	 * 
	 * @var number
	 */
	const KIND_TEXT_MSG = 1;
	/**
	 * Photo message kind
	 * 
	 * @var number
	 */
	const KIND_PHOTO_MSG = 2;
	/**
	 * Audio message kind
	 * 
	 * @var number
	 */
	const KIND_AUDIO_MSG = 4;
	/**
	 * Video message kind
	 * 
	 * @var number
	 */
	const KIND_VIDEO_MSG = 8;

	/**
	 * Message should be send
     *
	 * @var number
	 */
	const MESSAGE_STATUS_TO_SEND = 1;
	
	/**
	 * Message should be resend
	 * 
	 * @var number
	 */
	const MESSAGE_STATUS_TO_RETRY = 2;
	
	/**
	 * Message sent to server
	 * 
	 * @var number
	 */
	const MESSAGE_STATUS_SENT = 4;
	
	/**
	 * Message has been confirmed by server
	 * 
	 * @var number
	 */
	const MESSAGE_STATUS_RECEIVED_BY_SERVER = 5;

    const MESSAGE_STATUS_RECEIVED_BY_PHONE = 6;

    /**
	 * There was an error during sending message
	 * 
	 * @var number
	 */
	const MESSAGE_STATUS_ERROR = 8;

    /**
     * Message resent
     *
     * @var number
     */
    const MESSAGE_STATUS_RETRIED = 16;

    /**
	 * Id of message
	 * 
	 * @var unknown
	 */
	private $id;

    /**
     * Id of group
     *
     * @var number
     */
    private $group_id;

    /**
     * Id of sender
     *
     * @var number
     */
    private $sender_id;

    /**
	 * Id of user who last modifed the message
	 * 
	 * @var number
	 */
	private $user_id;

	/**
	 * Kind of message
	 * 
	 * See KIND_* consts
	 * 
	 * @var unknown
	 */
	private $kind;
	
	/**
	 * Target is a comma separated list of MSISDNs of receivers
	 * 
	 * @var string
	 */
	private $target;
	
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
	 * Scheduled timestamp
	 * 
	 * @var number
	 */
	private $stime;

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
     * Id of group
     */
    public function getGroupId() {
        return $this->group_id;
    }

    public function setGroupId($group_id) {
        $this->group_id = $group_id;
    }

    /**
     * Id of sender
     */
    public function getSenderId() {
        return $this->sender_id;
    }

    public function setSenderId($sender_id) {
        $this->sender_id = $sender_id;
    }

    /**
	 * Id of user who last modifed the message
	 */
	public function getUserId() {
		return $this->user_id;
	}

	public function setUserId($user_id) {
		$this->user_id = $user_id;
	}

	/**
	 * Kind of message
	 *
	 * See KIND_* consts
	 */
	public function getKind() {
		return $this->kind;
	}

	public function setKind($kind) {
		$this->kind = $kind;
	}

	/**
	 * Target is a comma separated list of MSISDNs of receivers
	 */
	public function getTarget() {
		return $this->target;
	}

	public function setTarget($target) {
		$this->target = $target;
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
	 * Scheduled timestamp
	 */
	public function getStime() {
		return $this->stime;
	}

	public function setStime($stime) {
		$this->stime = $stime;
	}

}
