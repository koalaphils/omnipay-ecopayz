<?php

namespace TransactionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * Description of StatusType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class MessageType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('customer', Type\TextareaType::class, [
            'label' => 'fields.details.messages.customer',
            'translation_domain' => 'TransactionBundle',
            'mapped' => array_get($builder->getOption('unmap'), 'customer', true),
        ])->add('support', Type\TextareaType::class, [
            'label' => 'fields.details.messages.support',
            'translation_domain' => 'TransactionBundle',
            'mapped' => array_get($builder->getOption('unmap'), 'support', true),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => 'default',
            'view' => false,
            'views' => [],
            'unmap' => [],
        ]);
    }

    public function finishView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        if ($form->getConfig()->getOption('view')) {
            foreach ($view->children as &$child) {
                $child->vars['view'] = true;
            }
        }

        foreach ($form->getConfig()->getOption('views') as $field => $isView) {
            $view->children[$field]->vars['view'] = $isView;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'bonus';
    }
}
