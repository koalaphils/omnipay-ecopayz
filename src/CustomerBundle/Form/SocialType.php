<?php

namespace CustomerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * Description of SocialType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class SocialType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', Type\ChoiceType::class, [
                'label' => 'fields.social.type',
                'translation_domain' => 'CustomerBundle',
                'choices' => [
                    'socialType.skype' => 'skype',
                    'socialType.facebook' => 'facebook',
                    'socialType.website' => 'website',
                ],
                'choices_as_values' => true,
            ])
            ->add('value', Type\TextType::class, [
                'label' => 'fields.social.value',
                'translation_domain' => 'CustomerBundle',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'social';
    }
}
