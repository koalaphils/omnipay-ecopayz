<?php

namespace TransactionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;

/**
 * Description of StatusType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class StatusType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label', Type\TextType::class, [
            'label' => 'settings.transaction.fields.status.cms_label',
            'required' => true,
            'translation_domain' => 'TransactionBundle',
            'attr' => ['class' => 't-status-label'],
        ])->add('amsLabel', Type\TextType::class, [
            'label' => 'settings.transaction.fields.status.ams_label',
            'required' => true,
            'translation_domain' => 'TransactionBundle',
            'attr' => ['class' => 't-ams-status-label'],
        ])->add('editBonusAmount', CType\SwitchType::class, [
            'label' => 'settings.transaction.fields.status.edit_bonus_amount',
            'translation_domain' => 'TransactionBundle',
            'required' => false,
        ])
        ->add('editAmount', CType\SwitchType::class, [
            'label' => 'settings.transaction.fields.status.edit_amount',
            'translation_domain' => 'TransactionBundle',
            'required' => false,
        ])
        ->add('editDate', CType\SwitchType::class, [
                'label' => 'settings.transaction.fields.status.edit_date',
                'translation_domain' => 'TransactionBundle',
                'required' => false,
        ])->add('editRemark', CType\SwitchType::class, [
                'label' => 'settings.memberRequest.fields.status.edit_remark',
                'translation_domain' => 'TransactionBundle',
                'required' => false,
        ])->add('editGateway', CType\SwitchType::class, [
            'label' => 'settings.transaction.fields.status.editGateway',
            'translation_domain' => 'TransactionBundle',
            'required' => false,
        ])->add('editFees', CType\SwitchType::class, [
            'label' => 'settings.transaction.fields.status.editFees',
            'translation_domain' => 'TransactionBundle',
            'required' => false,
        ])->add('actions', Type\CollectionType::class, [
            'label' => 'settings.transaction.fields.status.actions',
            'translation_domain' => 'TransactionBundle',
            'entry_type' => ActionType::class,
            'allow_add' => true,
            'prototype' => true,
            'prototype_name' => '__action_name__',
            'entry_options' => [
                'statuses' => $builder->getOption('statuses'),
            ],
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
            'statuses' => [],
        ]);
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
        return 'status';
    }
}
