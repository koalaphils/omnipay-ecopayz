<?php

namespace AppBundle\Twig;

use AppBundle\Manager\MenuManager;
use AppBundle\Manager\SettingManager;

class MenuTwigExtension extends \Twig_Extension
{
    /**
     * @var \AppBundle\Manager\MenuManager
     */
    protected $menuManager;

    /**
     * @var \AppBundle\Manager\SettingManager
     */
    protected $settingManager;

    public function __construct(MenuManager $menuManager, SettingManager $settingManager)
    {
        $this->menuManager = $menuManager;
        $this->settingManager = $settingManager;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('getMenus', [$this, 'getMenus']),
            new \Twig_SimpleFunction('getSettingMenus', [$this, 'getSettingMenus']),
            new \Twig_SimpleFunction('setActiveMenu', [$this, 'setActiveMenu']),
        ];
    }

    public function setActiveMenu($menu)
    {
        $this->menuManager->setActive($menu);
    }

    public function getMenus()
    {
        return $this->menuManager->getMenu();
    }

    public function getSettingMenus()
    {
        return $this->settingManager->getSettingMenus();
    }
}
