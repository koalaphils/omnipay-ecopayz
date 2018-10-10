<?php

namespace WebSocketBundle\Manager;

class TopicManager
{
    protected $topics;

    public function __construct()
    {
        $this->topics = [];
    }

    public function addTopic($topic, $uri)
    {
        $this->topics[$uri] = $topic;
    }

    public function getTopic($uri = null)
    {
        if ($uri === null) {
            return $this->topics;
        }
        if (array_has($this->topics, $uri)) {
            return $this->topics[$uri];
        }

        return null;
    }
}
