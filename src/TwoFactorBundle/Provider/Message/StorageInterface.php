<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message;

use TwoFactorBundle\Provider\Message\Exceptions\CodeDoesNotExistsException;

interface StorageInterface
{
    public function saveCode(CodeModel $model): void;

    /**
     * @param string $code
     * @return CodeModel
     *
     * @throws CodeDoesNotExistsException
     */
    public function getCode(string $code): CodeModel;
}