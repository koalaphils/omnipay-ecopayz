<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\DataTransfer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Datatable DataTransfer
 */
class DatatableDataTransfer implements DataTransferInterface
{
    public function __construct(DataTransfer $dataTransfer)
    {
        $this->dataTransfer = $dataTransfer;
    }

    public function transform($pagination, $args = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $options = $resolver->resolve($args);

        if (!isset($options['data_transfer'])) {
            throw new CoreException("Provide 'data_transfer' index in options when using PaginationDataTransfer as your DataTransfer class.");
        }

        $dataTransferClass = $options['data_transfer'];
        $preferredResultName = isset($options['result_name']) ? $options['result_name'] : 'data';

        /*$aPagination = [];

        foreach ($pagination->getItems() as $item) {
            $aPagination[$preferredResultName][] = $this->dataTransfer->transform(
                $dataTransferClass,
                $item,
                []
            );
        }

        $aPagination['pagination'] = $pagination->getPaginationData();

        return $aPagination;*/
        $dataTable = [
            'draw' => $options['draw'],
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            $preferredResultName => [],
        ];

        return $dataTable;
    }

    public function reverseTransform($object, $args = array())
    {
        return [];
    }

    private function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['draw', 'totalFiltered']);
    }
}
