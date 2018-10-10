<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RepeatableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('repeatable', true);
    }

    public function buildView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        /* @var $prototype \Symfony\Component\Form\FormInterface */
        $prototype = $form->getConfig()->getAttribute('prototype');
        $view->vars['prototype'] = $prototype->setParent($form)->createView($view);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('allow_extra_fields', true);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'repeatable';
    }
}
