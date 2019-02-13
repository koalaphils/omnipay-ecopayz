<?php

namespace PaymentBundle\TokenFactory;

use DbBundle\Entity\Customer as Member;
use PaymentBundle\Model\MemberToken;
use Payum\Core\Security\TokenFactoryInterface;
use Payum\Core\Security\TokenInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MemberTokenFactory implements TokenFactoryInterface
{
    private $urlGenerator;

    public function createToken(
        $gatewayName,
        $model,
        $targetPath,
        array $targetParameters = [],
        $afterPath = null,
        array $afterParameters = []
    ): TokenInterface {
        if (!($model instanceof Member)) {
            throw new \RuntimeException('Only ' . Member::class, ' is accespted as model');
        }

        $token = new MemberToken();
        $token->setHash(base64_encode($model->getId()));
        $token->setGatewayName($gatewayName);
        $token->setDetails(['customer' => $model->getId()]);

        $targetParameters = array_replace(['hash' => $token->getHash()], $targetParameters);
        $token->setTargetUrl($this->urlGenerator->generate($targetPath, $targetParameters, UrlGeneratorInterface::ABSOLUTE_URL));
        if (!is_null($afterPath)) {
            $afterParameters = array_replace(['hash' => $token->getHash()], $afterParameters);
            $token->setAfterUrl($this->urlGenerator->generate($afterPath, $afterParameters, UrlGeneratorInterface::ABSOLUTE_URL));
        }

        return $token;
    }

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }
}
