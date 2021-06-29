<?php

namespace UserBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use AppBundle\Form\Type\SwitchType;
use DbBundle\Entity\User;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserType extends AbstractType
{
    private $doctrine;
    private $router;
    private $authorizationChecker;

    public function __construct(
        Registry $doctrine,
        Router $router,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->doctrine = $doctrine;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
    }
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dataUser = $builder->getData();

        $builder->add('username', TextType::class, [
            'label' => 'fields.username',
            'required' => true,
            'translation_domain' => 'UserBundle',
        ])
                ->add('changePassword', SwitchType::class, [
                    'label' => 'fields.changePassword',
                    'required' => false,
                    'translation_domain' => 'UserBundle',
                    'data' => false,
                    'mapped' => false,
                ])
                ->add('password', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'required' => true,
                    'invalid_message' => 'password.mismatch',
                    'first_options' => ['label' => 'fields.password', 'translation_domain' => 'UserBundle'],
                    'second_options' => ['label' => 'fields.confirmPassword', 'translation_domain' => 'UserBundle'],
                ])
                ->add('email', EmailType::class, [
                    'label' => 'fields.email',
                    'required' => true,
                    'translation_domain' => 'UserBundle',
                ])
                ->add('isActive', SwitchType::class, [
                    'label' => 'fields.isActive',
                    'required' => false,
                    'translation_domain' => 'UserBundle',
                ])
                ->add('group', \AppBundle\Form\Type\Select2Type::class, [
                    'label' => 'fields.group',
                    'translation_domain' => 'UserBundle',
                    'attr' => [
                        'data-autostart' => 'true',
                        'data-ajax--url' => $this->router->generate('group.list_search'),
                        'data-ajax--type' => 'POST',
                        'data-minimum-input-length' => 0,
                        'data-length' => 10,
                    ],
                    'required' => false,
                ])
                ->add('roles', \AppBundle\Form\Type\RolesType::class, [
                    'label' => 'fields.roles',
                    'translation_domain' => 'UserBundle',
                    'property_path' => 'rolesPlain',
                    'inherit_roles' => ($dataUser instanceof User) ? $dataUser->getRoles() : []
                ])
                ->add('save', SubmitType::class, [
                    'label' => 'form.save',
                    'translation_domain' => 'AppBundle',
                    'attr' => [
                        'class' => 'btn-success pull-right m-r-10'
                    ],
                ]);

        $builder
            ->get('group')
                ->addViewTransformer(new CallbackTransformer(
                    function ($group) {
                        if ($group && !is_array($group) && $group->getId()) {
                            $groupEntity = $this->getUserGroupRepository()->find($group);

                            return [['id' => $groupEntity->getId(), 'text' => $groupEntity->getName()]];
                        }

                        return $group;
                    },
                    function ($group) {
                        if ($group) {
                            return $this->getUserGroupRepository()->find($group);
                        }

                        return null;
                    }
                ))
                ->addModelTransformer(new CallbackTransformer(
                    function ($group) {
                        if ($group && $group->getId()) {
                            $groupEntity = $this->getUserGroupRepository()->find($group->getId());

                            return [['id' => $groupEntity->getId(), 'text' => $groupEntity->getName()]];
                        }

                        return null;
                    },
                    function ($group) {
                        $groupEntity = null;
                        if ($group) {
                            $groupEntity = $this->getUserGroupRepository()->find($group);
                        }

                        return $groupEntity;
                    }
                ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $user = $event->getData();
            $form = $event->getForm();

            if (!$user) {
                $form->remove('changePassword');
            }
        });

        if (($dataUser instanceof User) && !$this->authorizationChecker->isGranted('ROLE_CHANGE_USER_ROLES')) {
            $builder->remove('roles');
            $builder->remove('group');
        } elseif (($dataUser instanceof User) && !$this->authorizationChecker->isGranted('ROLE_CHANGE_USER_GROUP')) {
            $builder->remove('group');
        } elseif (!($dataUser instanceof User) && !$this->authorizationChecker->isGranted('ROLE_ADD_USER_ROLES')) {
            $builder->remove('roles');
            $builder->remove('group');
        } elseif (!($dataUser instanceof User) && !$this->authorizationChecker->isGranted('ROLE_ADD_USER_GROUP')) {
            $builder->remove('group');
        }

        if (($dataUser instanceof User) && $dataUser->isSuperAdmin() && !$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->remove('roles');
            $builder->remove('group');
        }
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
            'csrf_token_id' => 'user',
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'User';
    }

    /**
     * @return \DbBundle\Repository\UserGroupRepository
     */
    public function getUserGroupRepository()
    {
        return $this->doctrine->getRepository('DbBundle:UserGroup');
    }
}
