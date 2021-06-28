<?php

namespace AppBundle\DoctrineExtension\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * @author Cydrick Nonog <cydrick.dev@gmail.com>
 */
class TinyIntType extends Type
{
    const TINYINT = 'tinyint';

    public function getName()
    {
        return self::TINYINT;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if (method_exists($platform, 'getTinyIntTypeDeclarationSQL')) {
            return $platform->getTinyIntTypeDeclarationSQL();
        }
        $autoinc = '';
        if (!empty($fieldDeclaration['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT';
        }
        $unsigned = (isset($fieldDeclaration['unsigned']) && $fieldDeclaration['unsigned']) ? ' UNSIGNED' : '';

        return 'TINYINT' . $unsigned . $autoinc;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return (null === $value) ? null : (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType()
    {
        return \PDO::PARAM_INT;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
