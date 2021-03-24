<?php

namespace AppBundle\Interfaces;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface WidgetInterface
{
    public static function defineDetails(): array;
    public function getProperties(): array;
    public function property(string $name, $default = null);
    public function setProperties(array $properties);
    public function validateProperties(array $properties);
    public function render();
}
