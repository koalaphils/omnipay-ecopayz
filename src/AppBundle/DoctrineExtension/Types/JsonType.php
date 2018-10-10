<?php

namespace AppBundle\DoctrineExtension\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class JsonType extends Type
{
    const JSON = 'json';

    public function getName()
    {
        return self::JSON;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'JSON';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        return json_encode($value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return [];
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        return json_decode($value, true);
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return !$platform->hasNativeJsonType();
    }
}
