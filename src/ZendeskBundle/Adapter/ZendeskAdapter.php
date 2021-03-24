<?php

namespace ZendeskBundle\Adapter;

/**
 * Zendesk Adapter
 *
 * This class is an adapter for zendesk to fit
 * for requirements of the system.
 */
class ZendeskAdapter
{
    private $originalObject;

    private $attributes;

    private function __construct($attributes, $originalObject)
    {
        $this->attributes = $attributes;
        $this->originalObject = $originalObject;
    }

    public function __call($name, $arguments)
    {
        $method = substr($name, 0, 3);
        $attribute = substr($name, 3);

        if (!array_key_exists(camel_case($attribute), $this->attributes)) {
            throw new \ZendeskBundle\Exceptions\PropertyNotExistsException();
        }

        if ($method === 'get') {
            return $this->getAttr(camel_case($attribute));
        }

        if ($method === 'set') {
            if (empty($arguments)) {
                throw new Exception("Not enough arguments");
            }
            $value = $arguments[0];
            $originalName = camel_case($attribute);
            if (count($arguments) === 2) {
                $originalName = $arguments[1];
            }

            return $this->setAttr(camel_case($attribute), $value, $originalName);
        }

        throw new \ZendeskBundle\Exceptions\InvalidMethodException();
    }

    public function getOriginalObject()
    {
        return $this->originalObject;
    }

    /**
     * Process the zendesk snake case attributes
     * to convert to camel case
     *
     * @param \stdClass $data
     *
     * @return \ZendeskBundle\Adapter\ZendeskAdapter
     */
    public static function create($data)
    {
        $properties = $data;
        if ($data instanceof \stdClass) {
            $properties = get_object_vars($data);
        }

        $attributes = [];

        foreach ($properties as $key => $value) {
            $formatedKey = camel_case($key);
            $attributes[$formatedKey] = $value;
        }

        return new ZendeskAdapter($attributes, $data);
    }

    private function getAttr($name)
    {
        return $this->attributes[$name];
    }

    private function setAttr($name, $value, $originalName)
    {
        if ($this->originalObject instanceof \stdClass) {
            $this->originalObject->{$originalName} = $value;
        } elseif (is_array($this->originalObject)) {
            $this->originalObject[$originalName] = $value;
        }

        $this->attributes[$name] = $value;

        return $this;
    }
}
