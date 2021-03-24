<?php

declare(strict_types = 1);

namespace AppBundle\DoctrineExtension\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

class UTCDateTimeType extends DateTimeType
{
    /**
     * @var \DateTimeZone
     */
    private static $utc;

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof \DateTimeImmutable) {
            $value = \DateTime::createFromFormat(\DateTime::ISO8601, $value->format(\DateTimeImmutable::ISO8601));
        } elseif (is_string($value)) {
            $value = new \DateTime($value);
        }

        if ($value instanceof \DateTime) {
            $value->setTimezone(self::getUTC());
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof \DateTime) {
            return $value;
        }

        $converted = \DateTime::createFromFormat(
            $platform->getDateTimeFormatString(),
            $value,
            self::getUTC()
        );

        if (!$converted) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        return $converted;
    }

    private static function getUTC(): \DateTimeZone
    {
        if (self::$utc == null) {
            self::$utc = new \DateTimeZone('UTC');
        }

        return self::$utc;
    }
}