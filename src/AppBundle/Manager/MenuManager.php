<?php

namespace AppBundle\Manager;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class MenuManager
{
    /**
     * @var \Symfony\Component\HttpKernel\Kernel
     */
    protected $kernel;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected $router;

    /**
     * @var array
     */
    private $menu = [];

    public function __construct(Kernel $kernel, Router $router)
    {
        $this->kernel = $kernel;
        $this->router = $router;
    }

    public function registerMenu()
    {
        foreach ($this->getKernel()->getBundles() as $bundle) {
            if (method_exists($bundle, 'registerMenu')) {
                $menus = $bundle->registerMenu();
                foreach ($menus as $menuKey => &$menu) {
                    $menu = array_replace_recursive($this->_default(), $menu);
                    $validate = $this->validatedMenu($menu);
                    if ($validate !== true) {
                        throw $validate;
                    }
                    $this->normalize($menu);
                    if (array_has($menu, 'subMenus')) {
                        $subMenus = array_get($menu, 'subMenus');
                        foreach ($subMenus as &$subMenu) {
                            $subMenu = array_replace_recursive($this->_default(), $subMenu);
                            $subMenuValidate = $this->validatedMenu($subMenu);
                            if ($subMenuValidate !== true) {
                                throw $subMenuValidate;
                            }
                            $this->normalize($subMenu);
                        }
                        $menu['subMenus'] = $subMenus;
                    }
                }

                $this->menu = array_replace_recursive($this->menu, $menus);
            }
        }
    }

    /**
     * Get menu.
     *
     * @param string|null $name
     *
     * @return array
     *
     * @throws Exception
     */
    public function getMenu($name = null)
    {
        if (null !== $name) {
            if (array_key_exists($name, $this->menu)) {
                return $this->menu[$name];
            }

            throw new \Exception("Menu $name was not available.", 1001);
        }
        
        return $this->menu;
    }

    public function setActive($menu)
    {
        $menus = explode('.', $menu);
        if (count($menus) > 1) {
            $this->menu[$menus[0]]['subMenus'][$menus[1]]['active'] = true;
        } else {
            $this->menu[$menus[0]]['active'] = true;
        }
    }

    protected function validatedMenu($menu)
    {
        if (!array_has($menu, 'label')) {
            return new \Exception('label key must be exist');
        }

        if (!array_has($menu, 'uri')) {
            return new \Exception('uri key must be exist');
        }

        if (is_array($menu['uri']) && !array_has($menu, 'uri.path') && !array_has($menu, 'uri.name')) {
            return new \Exception('key path or route must exist in uri');
        }

        return true;
    }

    protected function normalize(&$menu)
    {
        if (is_array($menu['uri'])) {
            $menu['uri']['name'] = array_get($menu, 'uri.name', null);
            $menu['uri']['params'] = array_get($menu, 'uri.params', []);
            $menu['uri']['path'] = $this->getRouter()->generate($menu['uri']['name'], $menu['uri']['params']);
        } else {
            $menu['uri'] = [
                'path' => $menu['uri'],
            ];
            $menu['uri']['name'] = null;
            $menu['uri']['params'] = [];
        }
    }

    protected function _default()
    {
        return [
            'permission' => ['ROLE_ADMIN'],
            'translation_domain' => 'messages',
            'active' => false,
        ];
    }

    /**
     * @return \AppKernel
     */
    protected function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Get router.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected function getRouter()
    {
        return $this->router;
    }
}
