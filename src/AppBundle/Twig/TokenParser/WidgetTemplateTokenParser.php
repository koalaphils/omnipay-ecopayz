<?php

namespace AppBundle\Twig\TokenParser;

use AppBundle\Twig\Node\WidgetTemplateNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class WidgetTemplateTokenParser extends AbstractTokenParser
{
    public function getTag(): string
    {
        return 'widget_template';
    }

    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();

        $widget = $this->parser->getExpressionParser()->parseExpression();
        $template = $this->parser->getExpressionParser()->parseExpression();

        $stream->expect(Token::BLOCK_END_TYPE);

        return new WidgetTemplateNode($widget, $template, $token->getLine(), $this->getTag());
    }
}
