<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class RolesType extends AbstractType
{
    /**
     * @var \AppBundle\Manager\RoleManager
     */
    protected $roleManager;

    public function __construct(\AppBundle\Manager\RoleManager $roleManager)
    {
        $this->roleManager = $roleManager;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $groups = $this->roleManager->getGroups();
        $roles = $this->roleManager->getCurrentUserRoles();
        foreach ($options['inherit_roles'] as $roleName) {
            if (
                !in_array(strtoupper(str_replace('.', '_', $roleName)), ['ROLE_ADMIN', 'ROLE_CUSTOMER', 'ROLE_SUPER_ADMIN'])
                && !array_has($roles, strtoupper(str_replace('.', '_', $roleName)))
            ) {
                $roles[$roleName] = $this->roleManager->getRole(strtoupper(str_replace('.', '_', $roleName)));
            }
        }

        $roleGroups = [];
        foreach ($groups as $groupName => $groupRoles) {
            $roleGroup = [];
            foreach ($groupRoles as $role) {
                if (array_has($roles, $role)) {
                    $roleGroup[] = $role;
                }
            }
            if (!empty($roleGroup)) {
                $roleGroups[$groupName] = $roleGroup;
            }
        }

        $view->vars['groups'] = $roleGroups;
        $view->vars['roles'] = $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['compound' => false, 'inherit_roles' => []]);
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
        return 'roles';
    }
}
