<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;

/**
 * Description of SecurityType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class SecurityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('password', Type\RepeatedType::class, [
            'type' => Type\PasswordType::class,
            'required' => false,
            'invalid_message' => 'user.password.mismatch',
            'first_options' => ['label' => 'fields.password', 'translation_domain' => 'UserBundle'],
            'second_options' => ['label' => 'fields.confirmPassword', 'translation_domain' => 'UserBundle'],
            'mapped' => $builder->getOption('password_mapped', true),
        ])->add('email', Type\EmailType::class, [
                    'label' => 'fields.email',
                    'required' => true,
                    'translation_domain' => 'UserBundle',
        ])->add('actions', CType\GroupType::class, [
            'attr' => [
                'class' => 'pull-right',
            ],
        ]);
        $builder->get('actions')->add('save', Type\SubmitType::class, [
            'label' => 'form.save',
            'translation_domain' => 'AppBundle',
            'attr' => [
                'class' => 'btn-success',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\User',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'userSecurity',
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'UserSecurity';
    }
}
