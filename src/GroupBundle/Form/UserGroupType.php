<?php

namespace GroupBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use AppBundle\Manager\RoleManager;

class UserGroupType extends AbstractType
{
    private $roleManager;
    private $translator;

    public function __construct(RoleManager $roleManager, TranslatorInterface $translator)
    {
        $this->roleManager = $roleManager;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
                    'label' => 'fields.name',
                    'required' => true,
                    'translation_domain' => 'GroupBundle',
                ])
                ->add('roles', ChoiceType::class, [
                    'label' => 'fields.roles',
                    'choices' => $this->getRoles(),
                    'multiple' => true,
                    'expanded' => true,
                    'translation_domain' => 'GroupBundle',
                    'group_by' => function ($roleInfo, $key, $index) {
                        return $roleInfo->group;
                    },
                    'choice_value' => function ($roleInfo) {
                        if (is_array($roleInfo)) {
                            return $roleInfo['value'];
                        }
                        
                        return $roleInfo->value;
                    }
                ])
                ->add('save', SubmitType::class, [
                    'label' => 'form.save',
                    'translation_domain' => 'AppBundle',
                ]);
    }

    public function getRoles()
    {
        $roles = $this->roleManager->getAllRoles();
        $groups = $this->roleManager->getGroups();
        $choices = [];

        foreach ($groups as $groupName => $groupRoles) {
            foreach ($groupRoles as $roleCode) {
                $role = $roles[$roleCode];
                $choices[$this->getTranslator()->trans(array_get($role, 'label.text'), [], array_get($role, 'label.translation_domain'))] = (object) [
                    'value' => $roleCode,
                    'group' => $groupName,
                ];
            }
        }

        return $choices;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\UserGroup',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'group',
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'UserGroup';
    }

    /**
     * Get translator.
     *
     * @return \Symfony\Component\Translation\DataCollectorTranslator
     */
    protected function getTranslator()
    {
        return $this->translator;
    }
}
