<?php

namespace AppBundle\Manager;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RoleManager
{
    /**
     * @var \Symfony\Component\HttpKernel\Kernel
     */
    protected $kernel;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    private $authorizationChecker;

    /**
     * @var array
     */
    private $roles = [];

    /**
     * @var array
     */
    private $groups = [];

    public function __construct(
        Kernel $kernel,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->kernel = $kernel;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function registerRoles()
    {
        foreach ($this->getKernel()->getBundles() as $bundle) {
            if (method_exists($bundle, 'registerRole')) {
                foreach ($bundle->registerRole() as $key => $info) {
                    if (is_array($info)) {
                        $this->setRole($key, $info);
                    }
                }
            }
        }
    }

    public function setRole($key, $info)
    {
        $translationDomain = array_get($info, 'translation_domain', 'messages');

        if (!array_has($info, 'label')) {
            throw new Exception('Label must define');
        }
        if (is_string(array_get($info, 'label'))) {
            array_set($info, 'label', ['text' => $info['label']]);
        }
        if (!array_has($info, 'label.translation_domain')) {
            array_set($info, 'label.translation_domain', $translationDomain);
        }

        if (!array_has($info, 'group')) {
            array_set($info, 'group', 'roles.groups.system');
        }
        if (is_string(array_get($info, 'group'))) {
            array_set($info, 'group', ['text' => $info['group']]);
        }
        if (!array_has($info, 'group.translation_domain')) {
            array_set($info, 'group.translation_domain', $translationDomain);
        }

        if (!array_has($info, 'requirements')) {
            $info['requirements'] = [];
        }

        array_set($this->roles, $key, $info);
        if (!array_has(
            $this->groups,
            $this->getTranslator()->trans(
                array_get($info, 'group.text'),
                [],
                array_get($info, 'group.translation_domain')
            )
        )) {
            array_set(
                $this->groups,
                $this->getTranslator()->trans(
                    array_get($info, 'group.text'),
                    [],
                    array_get($info, 'group.translation_domain')
                ),
                []
            );
        }

        $groupKey = $this->getTranslator()->trans(array_get($info, 'group.text'), [], array_get($info, 'group.translation_domain'));

        $this->groups[$groupKey] = array_append(
            $this->groups[$groupKey],
            $key
        );
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function getAllRoles()
    {
        return $this->roles;
    }

    public function getCurrentUserRoles()
    {
        if (!$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $roles = [];
            foreach ($this->roles as $roleName => $role) {
                if ($this->authorizationChecker->isGranted($roleName)) {
                    $roles[$roleName] = $role;
                }
            }

            return $roles;
        } else {
            return $this->roles;
        }
    }

    public function getRole(string $role): array
    {
        return array_get($this->roles, $role);
    }

    /**
     * @return \AppKernel
     */
    protected function getKernel()
    {
        return $this->kernel;
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
