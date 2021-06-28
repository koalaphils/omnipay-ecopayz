<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DbBundle\DataTransfer;

use AppBundle\DataTransfer\DataTransferInterface;
use AppBundle\Component\DataTransfer;

/**
 * Description of CustomerListDataTransfer
 *
 * @author cnonog
 */
class CustomerGroupListDataTransfer implements DataTransferInterface
{
    private $dataTransfer;

    public function __construct(DataTransfer $dataTransfer)
    {
        $this->dataTransfer = $dataTransfer;
    }

    public function transform($customerGroups, $args = [])
    {
        $list = [];

        foreach ($customerGroups as $customerGroup) {
            $list[] = $this->dataTransfer->transform(
                CustomerGroupDataTransfer::class,
                $customerGroup,
                []
            );
        }

        return $list;
    }

    public function reverseTransform($object, $args = array())
    {
    }
}
