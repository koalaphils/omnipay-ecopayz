<?php

namespace AppBundle\Form\Type;

/**
 * Description of RepeatableRecordType
 *
 * @author cydrick
 */
class RepeatableRecordType extends \Symfony\Component\Form\AbstractType
{
    public function finishView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options) {
        foreach ($view->children as $childName => $child) {
            $child->vars['size'] = $form->get($childName)->getConfig()->getAttribute('size', 12);
        }
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'repeatableRecord';
    }
}
