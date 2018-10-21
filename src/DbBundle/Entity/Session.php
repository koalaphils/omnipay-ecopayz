<?php

namespace DbBundle\Entity;

class Session extends Entity
{
    //use Traits\ActionTrait;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var \DbBundle\Entity\User
     */
    protected $user;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return \DbBundle\Entity\Session
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return \DbBundle\Entity\Session
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set key.
     *
     * @param string $key
     *
     * @return \DbBundle\Entity\Session
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set user.
     *
     * @param \DbBundle\Entity\User $user
     *
     * @return \DbBundle\Entity\User
     */
    public function setUser($user)
    {
        $this->user = $user;

        $this->setUserId($user->getId());

        return $this;
    }

    /**
     * Get user.
     *
     * @return \DbBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setCreatedValue()
    {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return User
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}