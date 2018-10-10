<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;

/**
 * Bonus.
 */
class Bonus extends Entity implements ActionInterface, TimestampInterface
{
    use Traits\ActionTrait;
    use Traits\SoftDeleteTrait;
    use Traits\TimestampTrait;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var \DateTime
     */
    private $startAt;

    /**
     * @var \DateTime
     */
    private $endAt;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var string
     */
    private $terms;

    /**
     * @var json
     */
    private $image;

    /**
     * Set subject.
     *
     * @param string $subject
     *
     * @return Bonus
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set startAt.
     *
     * @param \DateTime $startAt
     *
     * @return Bonus
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
     * @return Bonus
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
     * @param bool $isActive
     *
     * @return Bonus
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set terms.
     *
     * @param string $terms
     *
     * @return Bonus
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * Get terms.
     *
     * @return string
     */
    public function getTerms()
    {
        return $this->terms;
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
