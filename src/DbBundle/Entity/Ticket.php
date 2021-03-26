<?php

namespace DbBundle\Entity;

class Ticket extends Entity
{
    const TICKET_TYPE_QUESTION = 'question';
    const TICKET_TYPE_INCIDENT = 'incident';
    const TICKET_TYPE_PROBLEM = 'problem';
    const TICKET_TYPE_TASK = 'task';

    const TICKET_PRIORITY_LOW = 'low';
    const TICKET_PRIORITY_NORMAL = 'normal';
    const TICKET_PRIORITY_HIGH = 'high';
    const TICKET_PRIORITY_URGENT = 'urgent';

    const TICKET_STATUS_OPEN = 'open';
    const TICKET_STATUS_PENDING = 'pending';
    const TICKET_STATUS_HOLD = 'hold';
    const TICKET_STATUS_SOLVED = 'solved';
    const TICKET_STATUS_CLOSED = 'closed';

    /**
     * @var int
     */
    private $ticketId;

    /**
     * @var string
     */
    private $requester;

    /**
     * @var string
     */
    private $assignee;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $priority;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $description;

    /**
     * @var array
     */
    private $tag;

    public function __construct()
    {
        $this->tag = [];
        //$this->type = array();
        //$this->priority = array();
    }

    public function setTicketId($v)
    {
        $this->ticketId = $v;

        return $this;
    }

    /**
     * Get requester.
     *
     * @param int $v
     *
     * @return string
     */
    public function setRequester($v)
    {
        $this->requester = $v;

        return $this;
    }

    public function setTag($v)
    {
        $this->tag = $v;

        return $this;
    }

    public function setAssignee($v)
    {
        $this->assignee = $v;

        return $this;
    }

    public function setStatus($v)
    {
        $this->status = $v;

        return $this;
    }

    public function setPriority($v)
    {
        $this->priority = $v;

        return $v;
    }

    public function setType($v)
    {
        $this->type = $v;

        return $this;
    }

    public function setSubject($v)
    {
        $this->subject = $v;

        return $this;
    }

    public function setDescription($v)
    {
        $this->description = $v;

        return $this;
    }

    public function getTicketId()
    {
        return $this->ticketId;
    }

    public function getRequester()
    {
        return $this->requester;
    }

    public function getTag()
    {
        $newTag = null;
        if (!empty($this->tag)) {
            foreach ($this->tag as $key => $tag) {
                $newTag .= $tag . ',';
            }
            $newTag = rtrim($newTag, ',');
        }

        return $newTag;
    }

    public function getAssignee()
    {
        return $this->assignee;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
