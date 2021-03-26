<?php

namespace Page;

use Codeception\Util\Locator;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\ParseException;


class PageObject
{
    protected $tester;

    public function __construct($tester)
    {
        $this->tester = $tester;
    }
    
    public function select2SelectItem(string $selector, string $item): void
    {
        $parentXpath = $this->toXPath($selector) . '/parent::div';
        $this->tester->click('.select2-container', $parentXpath);
        $this->tester->waitForElement('.select2-results__options', 20);
        $this->tester->waitForText($item, 20, '.select2-results__options');
        $this->tester->click(Locator::contains('.select2-results__options li', $item));
    }
    
    private static function toXPath($selector)
    {
        try {
            $xpath = (new CssSelectorConverter())->toXPath($selector);
            return $xpath;
        } catch (ParseException $e) {
            if (Locator::isXPath($selector)) {
                return $selector;
            }
        }
        return null;
    }
}