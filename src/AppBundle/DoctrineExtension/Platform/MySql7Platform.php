<?php

namespace AppBundle\DoctrineExtension\Platform;

use Doctrine\DBAL\Platforms\MySQL57Platform;

class MySql7Platform extends MySQL57Platform
{
    public function getColumnDeclarationSQL($name, array $field)
    {
        if (isset($field['columnDefinition'])) {
            $columnDef = $this->getCustomTypeDeclarationSQL($field);
        } else {
            $default = $this->getDefaultValueDeclarationSQL($field);

            $charset = (isset($field['charset']) && $field['charset']) ?
                    ' ' . $this->getColumnCharsetDeclarationSQL($field['charset']) : '';

            $collation = (isset($field['collation']) && $field['collation']) ?
                    ' ' . $this->getColumnCollationDeclarationSQL($field['collation']) : '';

            $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';

            $unique = (isset($field['unique']) && $field['unique']) ?
                    ' ' . $this->getUniqueFieldDeclarationSQL() : '';

            $check = (isset($field['check']) && $field['check']) ?
                    ' ' . $field['check'] : '';

            $typeDecl = $field['type']->getSQLDeclaration($field, $this);
            $columnDef = $typeDecl . $charset . $default . $notnull . $unique . $check . $collation;

            if (isset($field['generate'])) {
                if (isset($field['generateAlways']) && $field['generateAlways']) {
                    $columnDef .= ' GENERATED ALWAYS';
                }
                $columnDef .= " AS (" . $field['generate'] . ")" ;
                $columnDef .= isset($field['generateType']) ? ' ' . $field['generateType'] : '';
            }

            if ($this->supportsInlineColumnComments() && isset($field['comment']) && $field['comment'] !== '') {
                $columnDef .= ' ' . $this->getInlineColumnCommentSQL($field['comment']);
            }
        }

        return $name . ' ' . $columnDef;
    }

    public function getDefaultValueDeclarationSQL($field)
    {
        if (!isset($field['generate'])) {
            return parent::getDefaultValueDeclarationSQL($field);
        }

        return '';
    }

    public function supportsVirtualColumn()
    {
        return true;
    }
}
