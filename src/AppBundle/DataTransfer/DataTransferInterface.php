<?php

namespace AppBundle\DataTransfer;

/**
 * Data Transfer Interface
 */
interface DataTransferInterface
{
    public function transform($object, $args = []);
    public function reverseTransform($object, $args = []);
}
