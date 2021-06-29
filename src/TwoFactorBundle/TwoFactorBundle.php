<?php

declare(strict_types = 1);

namespace TwoFactorBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TwoFactorBundle\DependencyInjection\Compiler\TwoFactorProviderPass;
use TwoFactorBundle\Provider\TwoFactorProviderInterface;

class TwoFactorBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TwoFactorProviderPass());
    }
}