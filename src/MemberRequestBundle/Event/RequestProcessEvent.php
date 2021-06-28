<?php

namespace MemberRequestBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use DbBundle\Entity\MemberRequest as Request;

class RequestProcessEvent extends Event
{
    private $request;
    private $action;

    public function __construct(Request $request, array $action = [])
    {
        $this->request = $request;
        $this->action = $action;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getAction(): array
    {
        return $this->action;
    }
}