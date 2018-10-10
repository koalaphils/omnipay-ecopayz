<?php

namespace TransactionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * Description of ActionType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class ActionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label', Type\TextType::class, [
            'label' => 'settings.transaction.fields.action.label',
            'required' => true,
            'translation_domain' => 'TransactionBundle',
        ])->add('status', Type\ChoiceType::class, [
            'choices_as_values' => true,
            'choices' => $this->getChoices($builder),
            'label' => 'settings.transaction.fields.action.status',
            'required' => true,
            'translation_domain' => 'TransactionBundle',
        ])->add('class', Type\TextType::class, [
            'label' => 'settings.transaction.fields.action.class',
            'translation_domain' => 'TransactionBundle',
            'required' => false,
        ]);
    }

    public function getChoices(FormBuilderInterface $builder)
    {
        $statuses = $builder->getOption('statuses');
        $_statuses = [];
        foreach ($statuses as $skey => $status) {
            if (is_array($status)) {
                $_statuses[$status['label']] = $skey;
            }
        }

        return $_statuses;
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
        return 'action';
    }
}
