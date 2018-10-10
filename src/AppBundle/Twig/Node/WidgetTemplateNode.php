<?php

namespace AppBundle\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;

class WidgetTemplateNode extends Node
{
    public function __construct($widget, $template, $line, $tag)
    {
        parent::__construct(['widget' => $widget, 'template' => $template], [], $line, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            //->write('$this->env->getRuntime(\'app.widget_manager\')->setInheritTemplate(')
            ->subcompile($this->getNode('widget'))
            ->raw('->setInheritTemplatePath(')
            ->subcompile($this->getNode('template'))
            ->raw(");\n");
    }
}
