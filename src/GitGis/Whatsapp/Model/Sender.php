<?php

namespace GitGis\Whatsapp\Model;
/**
 * Sender entity
 */
class Sender {

    /**
     * Id of sender
     *
     * @var number
     */
    private $id;

    /**
     * MSISDN (phone number) associated with sender
     *
     * @var string
     */
    private $username;

    /**
     * String generated during registration (login)
     *
     * @var string
     */
    private $identity;

    /**
     * Human friendly name of sender
     *
     * @var string
     */
    private $nickname;

    /**
     * Password generated during registration
     *
     * @var string
     */
    private $password;

    /**
     * Owner id
     *
     * @var number
     */
    private $user_id;


    const FLAG_UNSYNC = 0x01;
    const FLAG_WAIT_FOR_PASS = 0x02;
    const FLAG_WAIT_FOR_SMS = 0x04;

    private $flags = 0;

    private $challenge_data = '';


    /**
     * Id of sender
     */
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    /**
     * MSISDN (phone number) associated with sender
     */
    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    /**
     * String generated during registration (login)
     */
    public function getIdentity() {
        return $this->identity;
    }

    public function setIdentity($identity) {
        $this->identity = $identity;
    }

    /**
     * Human friendly name of sender
     */
    public function getNickname() {
        return $this->nickname;
    }

    public function setNickname($nickname) {
        $this->nickname = $nickname;
    }

    /**
     * Password generated during registration
     */
    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
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

    /**
     * @param string $challenge_data
     */
    public function setChallengeData($challenge_data)
    {
        $this->challenge_data = $challenge_data;
    }

    /**
     * @return string
     */
    public function getChallengeData()
    {
        return $this->challenge_data;
    }

    /**
     * @param int $flags
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;
    }

    /**
     * @return int
     */
    public function getFlags()
    {
        return $this->flags;
    }

}
