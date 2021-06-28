<?php

namespace PaymentBundle\TokenFactory;

use DbBundle\Entity\Customer as Member;
use PaymentBundle\Model\MemberToken;
use Payum\Core\Security\TokenFactoryInterface;
use Payum\Core\Security\TokenInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MemberTokenFactory implements TokenFactoryInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var string
     */
    private $callbackHost;

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
        $token->setTargetUrl($this->generateUrl($targetPath, $targetParameters));
        if (!is_null($afterPath)) {
            $afterParameters = array_replace(['hash' => $token->getHash()], $afterParameters);
            $token->setAfterUrl($afterPath, $afterParameters);
        }

        return $token;
    }

    public function __construct(UrlGeneratorInterface $urlGenerator, string $callbackHost = '')
    {
        $this->urlGenerator = $urlGenerator;
        $this->callbackHost = $callbackHost;
    }

    private function generateUrl(string $targtePath, array $targetParameters): string
    {
        $callback = rtrim($this->callbackHost, '\/');
        if ($callback !== '') {
            return $callback . $this->urlGenerator->generate($targtePath, $targetParameters, UrlGeneratorInterface::ABSOLUTE_PATH);
        } else {
            return $this->urlGenerator->generate($targtePath, $targetParameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
