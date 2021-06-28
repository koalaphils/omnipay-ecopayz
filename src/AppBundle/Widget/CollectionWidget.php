<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Widget;

use Symfony\Component\Form\Extension\Core\Type;

/**
 * Description of CollectionWidget
 *
 * @author cnonog
 */
class CollectionWidget extends AbstractWidget
{
    public static function defineProperties($container): array
    {
        return [
            'type' => [
                'type' => Type\ChoiceType::class,
                'options' => [
                    'choices' => [
                        'Row' => 'row',
                        'Coloumn' => 'column',
                    ],
                ],
            ],
        ];
    }

    protected function getBlockName(): string
    {
        return 'collection';
    }

    public static function defineDetails(): array
    {
        return [
            'title' => 'Collection',
        ];
    }
}
