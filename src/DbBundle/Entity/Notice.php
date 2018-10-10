<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;

/**
 * Notice.
 */
class Notice extends Entity implements ActionInterface, TimestampInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    const NOTICE_TYPE_GENERAL = 1;
    const NOTICE_TYPE_DEPOSIT = 2;
    const NOTICE_TYPE_WITHDRAW = 3;
    const NOTICE_TYPE_BET = 4;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var tinyint
     */
    private $type;

    /**
     * @var \DateTime
     */
    private $startAt;

    /**
     * @var \DateTime
     */
    private $endAt;

    /**
     * @var tinyint
     */
    private $isActive;

    /**
     * @var string
     */
    private $image;

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Notice
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Notice
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set type.
     *
     * @param tinyint $type
     *
     * @return Notice
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return tinyint
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set startAt.
     *
     * @param \DateTime $startAt
     *
     * @return Notice
     */
    public function setStartAt($startAt)
    {
        $this->startAt = $startAt;

        return $this;
    }

    /**
     * Get startAt.
     *
     * @return \DateTime
     */
    public function getStartAt()
    {
        return $this->startAt;
    }

    /**
     * Set endAt.
     *
     * @param \DateTime $endAt
     *
     * @return Notice
     */
    public function setEndAt($endAt)
    {
        $this->endAt = $endAt;

        return $this;
    }

    /**
     * Get endAt.
     *
     * @return \DateTime
     */
    public function getEndAt()
    {
        return $this->endAt;
    }

    /**
     * Set isActive.
     *
     * @param tinyint $isActive
     *
     * @return Notice
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return tinyint
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    public static function getTypes()
    {
        return [
            'noticeType.bet' => self::NOTICE_TYPE_BET,
            'noticeType.deposit' => self::NOTICE_TYPE_DEPOSIT,
            'noticeType.general' => self::NOTICE_TYPE_GENERAL,
            'noticeType.withdraw' => self::NOTICE_TYPE_WITHDRAW,
        ];
    }

    /**
     * Set image.
     *
     * @param json $image
     *
     * @return Bonus
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return json
     */
    public function getImage()
    {
        return $this->image;
    }
}
