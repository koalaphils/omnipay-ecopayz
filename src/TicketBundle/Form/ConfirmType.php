<?php

namespace TicketBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use DbBundle\Entity\Confirm;

class ConfirmType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('confirm', ButtonType::class, [
            'label' => 'form.confirm',
            'translation_domain' => 'AppBundle',
            'attr' => [
                'class' => 'btn btn-primary waves-effect waves-light',
            ],
        ])->add('cancel', ButtonType::class, [
            'label' => 'form.cancel',
            'translation_domain' => 'AppBundle',
            'attr' => [
                'class' => 'btn btn-default waves-effect',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\Confirm',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'confirm',
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Confirm';
    }
}
