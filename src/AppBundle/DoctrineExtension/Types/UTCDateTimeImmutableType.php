<?php

declare(strict_types = 1);

namespace AppBundle\DoctrineExtension\Types;

use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

class UTCDateTimeImmutableType extends DateTimeImmutableType
{
    /**
     * @var \DateTimeZone
     */
    private static $utc;

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof \DateTimeImmutable) {
            $value = $value->setTimezone(self::getUTC());
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof \DateTimeImmutable) {
            return $value;
        }

        $converted = \DateTimeImmutable::createFromFormat(
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