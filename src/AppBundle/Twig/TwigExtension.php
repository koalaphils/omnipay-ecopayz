<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Twig;

use AppBundle\Interfaces\WidgetInterface;
use AppBundle\Twig\TokenParser\WidgetTemplateTokenParser;
use AppBundle\Widget\AbstractWidget;
use AppBundle\ValueObject\Number;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use function array_forget;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

/**
 * Description of UnsetTokenParser.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class TwigExtension extends Twig_Extension
{
    use ContainerAwareTrait;

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('array_forget', [$this, 'arrayForget']),
            new Twig_SimpleFunction('asset_add', [$this, 'assetAdd']),
            new Twig_SimpleFunction('asset_render', [$this, 'assetRender'], ['is_safe' => ['html']]),
            new Twig_SimpleFunction('setting', [$this, 'getSetting']),
            new Twig_SimpleFunction('parameter', [$this, 'getParameter']),
            new Twig_SimpleFunction('widget_render', [$this, 'widgetRender'], ['is_safe' => ['html']]),
            new Twig_SimpleFunction('widget_render_block', [$this, 'widgetRenderBlock'], ['is_safe' => ['html']]),
            new Twig_SimpleFunction('widget_template', [$this, 'widgetTemplate']),
            new Twig_SimpleFunction('number', [$this, 'number']),
        ];
    }

    public function getTokenParsers()
    {
        return [new WidgetTemplateTokenParser()];
    }

    public function widgetTemplate(WidgetInterface $widget, string $templatePath)
    {
        $widget->setInheritTemplatePath($templatePath);
    }

    public function widgetRender(WidgetInterface $widget, $options = [], $definitions = [])
    {
        return $widget->render($options);
    }

    public function widgetRenderBlock(WidgetInterface $widget, string $block, $options = [], $definitions = [])
    {
        return $widget->renderBlock($block, $options);
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('format_number', [$this, 'formatNumber']),
            new Twig_SimpleFilter('json_decode', [$this, 'jsonDecode']),
            new Twig_SimpleFilter('round_format', [$this, 'numberFormatRound']),
        ];
    }

    public function jsonDecode($str)
    {
        return json_decode($str);
    }

    public function arrayForget($array, $keys)
    {
        array_forget($array, $keys);

        return $array;
    }

    public function formatNumber($number, $config = [])
    {
        return Number::format($number, $config);
    }

    public function assetAdd($type, $asset, $group = 'default')
    {
        if ($type === 'js') {
            $this->container->get('app.asset_manager')->addJs($asset, $group);
        } elseif ($type === 'css') {
            $this->container->get('app.asset_manager')->addCss($asset, $group);
        }
    }

    public function getSetting($path = '', $convert = true)
    {
        $setting = $this->container->get('app.setting_manager')->getSetting($path);
        if (is_array($setting) && $convert) {
            return json_encode($setting, true);
        }

        return $setting;
    }

    public function getParameter($name)
    {
        return $this->container->getParameter($name);
    }

    public function assetRender($type, $group = 'default', $asset = [], $force = false)
    {
        if ($type === 'js') {
            return $this->container->get('app.asset_manager')->renderJs($group, $asset, $force);
        }

        if ($type === 'css') {
            return $this->container->get('app.asset_manager')->renderCss($group, $asset, $force);
        }
    }

    public function number($number, array $config = []): Number
    {
        return new Number($number, $config);
    }

    public function numberFormatRound($number, int $decimal, string $decimalSeparation = ',', string $thousandSeparation = '.', int $type = Number::ROUND_DOWN): string
    {
        return number_format(Number::round($number, $decimal, $type), $decimal, $decimalSeparation, $thousandSeparation);
    }
}
