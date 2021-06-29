<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Widget;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints;

/**
 * Description of CounterWidge
 *
 * @author cnonog
 */
class QuotesWidget extends AbstractWidget
{
    private $quotes;

    public static function defineDetails(): array
    {
        return [
            'title' => 'Quotes',
        ];
    }

    public static function defineProperties($container): array
    {
        return [
            'count' => [
                'type' => Type\NumberType::class,
                'options' => [
                    'constraints' => [
                        new \AppBundle\Validator\Constraints\IsNumeric(),
                    ],
                ],
            ],
        ];
    }

    public function getQuotes(): array
    {
        shuffle($this->quotes);
        $quotes = [];
        for ($i = 0; $i < $this->property('count', 1); ++$i) {
            $quotes[] = $this->quotes[$i];
        }

        return $quotes;
    }

    protected function onInit()
    {
        $this->quotes = [
            ['quote' => 'Welcome to Asianconnect Backoffice', 'source' => '-']

        ];
    }

    protected function getBlockName(): string
    {
        return 'quotes';
    }
}
