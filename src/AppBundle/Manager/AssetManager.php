<?php

namespace AppBundle\Manager;

/**
 * Description of SettingManager.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class AssetManager
{
    protected $js = [];
    protected $css = [];
    protected $jsRendered = [];
    protected $cssRendered = [];

    public function addJs($uri, $group = 'default')
    {
        $js = ['uri' => $uri, 'group' => $group];
        if (!in_array($js, $this->jsRendered)) {
            if (!in_array($js, $this->js)) {
                $this->js[] = $js;
            }
        }
    }

    public function addCss($uri, $group = 'default')
    {
        $css = ['uri' => $uri, 'group' => $group];
        if (!in_array($css, $this->cssRendered)) {
            if (!in_array($css, $this->css)) {
                $this->css[] = $css;
            }
        }
    }

    public function renderJs($group = 'default', $js = [], $force = false)
    {
        $jss = [];
        if (empty($js)) {
            foreach ($this->js as $jsObj) {
                if ($jsObj['group'] === $group) {
                    $jss[] = $jsObj;
                }
            }
            if ($force) {
                foreach ($this->jsRendered as $jsObj) {
                    if ($jsObj['group'] === $group) {
                        $jss[] = $jsObj;
                    }
                }
            }
        } elseif (!empty($js)) {
            foreach ($js as $jsObj) {
                $_jsObj = ['uri' => $jsObj, 'group' => $group];
                if (in_array($_jsObj, $this->js)) {
                    $jss[] = $_jsObj;
                } else {
                    $this->js[] = $_jsObj;
                    $jss[] = $_jsObj;
                }
            }
            if ($force) {
                foreach ($js as $jsObj) {
                    $_jsObj = ['uri' => $jsObj, 'group' => $group];
                    if (in_array($_jsObj, $this->jsRendered)) {
                        $jss[] = $_jsObj;
                    }
                }
            }
        } else {
            $jss = $this->js;
            if ($force) {
                $jss = array_merge($jss, $this->jsRendered);
            }
        }
        $renderer = '';
        foreach ($jss as $jsObj) {
            $key = array_search($jsObj, $this->js);
            unset($this->js[$key]);
            if (!in_array($jsObj, $this->jsRendered)) {
                $this->jsRendered[] = $jsObj;
            }
            $renderer .= "<script type='text/javascript' src='$jsObj[uri]'></script>\n";
        }

        return $renderer;
    }

    public function renderCss($group = 'default', $css = [], $force = false)
    {
        $csss = [];
        if (empty($css)) {
            foreach ($this->css as $cssObj) {
                if ($cssObj['group'] === $group) {
                    $csss[] = $cssObj;
                }
            }
            if ($force) {
                foreach ($this->cssRendered as $cssObj) {
                    if ($cssObj['group'] === $group) {
                        $csss[] = $cssObj;
                    }
                }
            }
        } elseif (!empty($css)) {
            foreach ($css as $cssObj) {
                $_cssObj = ['uri' => $cssObj, 'group' => $group];
                if (in_array($_cssObj, $this->css)) {
                    $csss[] = $_cssObj;
                } else {
                    $this->css[] = $_cssObj;
                    $csss[] = $_cssObj;
                }
            }
            if ($force) {
                foreach ($css as $cssObj) {
                    $_cssObj = ['uri' => $cssObj, 'group' => $group];
                    if (in_array($_cssObj, $this->cssRendered)) {
                        $csss[] = $_cssObj;
                    }
                }
            }
        } else {
            $csss = $this->css;
            if ($force) {
                $jss = array_merge($csss, $this->cssRendered);
            }
        }
        $renderer = '';
        foreach ($csss as $cssObj) {
            $key = array_search($cssObj, $this->css);
            unset($this->css[$key]);
            if (!in_array($cssObj, $this->cssRendered)) {
                $this->cssRendered[] = $cssObj;
            }
            $renderer .= "<link type='text/css' rel='stylesheet' href='$cssObj[uri]' />\n";
        }

        return $renderer;
    }
}
