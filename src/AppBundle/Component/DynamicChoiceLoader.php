<?php

namespace AppBundle\Component;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

class DynamicChoiceLoader implements ChoiceLoaderInterface
{
    private $choiceList;

    public function loadChoiceList($value = null)
    {
        if (null !== $this->choiceList) {
            return $this->choiceList;
        }

        return $this->choiceList = new ArrayChoiceList([], $value);
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoicesForValues(array $values, $value = null)
    {
        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function loadValuesForChoices(array $choices, $value = null)
    {
        if (empty($choices)) {
            return array();
        }

        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

}
