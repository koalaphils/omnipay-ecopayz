<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

/**
 * Description of BaseType.
 *
 * @author cnonog
 */
class BaseType extends AbstractType
{
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $params = [];

        if (!$this->isMapped($options['mapped'])) {
            $params['view'] = true;
            $params['half'] = false;
        }

        $this->setParam($view, $params);
    }

    private function isMapped($mapped)
    {
        if ((is_bool($mapped) && $mapped === false)
            || (is_int($mapped) && $mapped === 0)
            || (is_string($mapped) && $mapped === '0')
        ) {
            return false;
        }

        return true;
    }

    private function setParam(FormView $view, array $params)
    {
        $this->updateParam($view, $params);
        $this->updateChild($view, $params);
    }

    private function updateChild(FormView $parent, array $params)
    {
        foreach ($parent->children as $child) {
            $this->updateParam($child, $params);
            $this->updateChild($child, $params);
        }
    }

    private function updateParam(FormView $view, array $params)
    {
        foreach ($params as $key => $value) {
            $view->vars[$key] = $value;
        }
    }
}
