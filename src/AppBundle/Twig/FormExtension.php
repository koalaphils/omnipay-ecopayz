<?php

namespace AppBundle\Twig;

/**
 * Description of FormExtension.
 *
 * @author cnonog
 */
class FormExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'form_javascript',
                null,
                ['node_class' => 'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode', 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'form_stylesheet',
                null,
                ['node_class' => 'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode', 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'form_assetcss',
                null,
                ['node_class' => 'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode', 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'form_assetjs',
                null,
                ['node_class' => 'Symfony\Bridge\Twig\Node\SearchAndRenderBlockNode', 'is_safe' => ['html']]
            ),
        ];
    }
}
