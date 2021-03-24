<?php

namespace CustomerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormInterface;

/**
 * Description of ContactType.
 *
 * @author cnonog
 */
class ContactType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', Type\ChoiceType::class, [
                'label' => 'fields.contact.type',
                'translation_domain' => 'CustomerBundle',
                'choices' => [
                    'contactType.mobile' => 'mobile',
                    'contactType.work' => 'work',
                    'contactType.home' => 'home',
                    'contactType.main' => 'main',
                    'contactType.workFax' => 'work_fax',
                    'contactType.fax' => 'fax',
                    'contactType.email' => 'email',
                ],
                'choices_as_values' => true,
            ])

            # regex ^(\+?(\d{3,6}))?[-. ]?(\d{3,7})[-. ]?(\d{1,7})$ matches the ff:
            # you may test using https://regexr.com/
            # 09293448617
            # +639293448617
            # 2222
            # 0929-344-8617
            # +63929-344-8617
            # 233-8657
            # +6302233-8657
            # 0929.344.8617
            ->add('value', Type\TextType::class, [
                'label' => 'fields.contact.value',
                'translation_domain' => 'CustomerBundle',
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'groups' => ['default']
                    ]),
                    new \Symfony\Component\Validator\Constraints\Regex([
                        'pattern' => '/^(\+?(\d{3,6}))?[-. ]?(\d{3,7})[-. ]?(\d{1,7})$/',
                        'groups' => ['mobile', 'work', 'home', 'main', 'fax', 'work_fax'],
                        'message' => '{{ value }} is not a valid contact number.'
                    ]),
                    new \Symfony\Component\Validator\Constraints\Email([
                        'groups' => ['email'],
                        'message' => 'Email is invalid.',
                    ])
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'customerContact',
            'validation_groups' => 'default',
            'validation_groups' => function (FormInterface $form) {
                $formData = $form->getData();
                $validationGroups = ['default'];

                if ($type = $formData['type']) {
                    $validationGroups[] = $type;
                }

                return $validationGroups;
            }
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact';
    }
}

