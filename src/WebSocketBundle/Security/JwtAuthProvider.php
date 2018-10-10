<?php

namespace WebSocketBundle\Security;

use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Thruway\Authentication\AbstractAuthProviderClient;
use Thruway\Message\Message;
use Thruway\Transport\TransportInterface;

class JwtAuthProvider extends AbstractAuthProviderClient
{
    use ContainerAwareTrait;

    protected $counters = null;

    private $jwtKey;

    public function __construct(array $authRealms, $jwtKey)
    {
        $this->jwtKey = $jwtKey;
        parent::__construct($authRealms);
    }

    public function getMethodName()
    {
        return 'jwt';
    }

    /**
     * Pre process AuthenticateMessage
     * Extract and validate arguments.
     *
     * @param array $args
     *
     * @return array
     */
    public function preProcessAuthenticate(array $args)
    {
        $args = $args[0];
        $signature = isset($args->signature) ? $args->signature : null;
        $extra = isset($args->extra) ? $args->extra : null;

        if (!$signature) {
            return ['ERROR'];
        }

        return $this->processAuthenticate($signature, $extra);
    }

    /**
     * Process HelloMessage.
     *
     * @param array $args
     *
     * @return array<string|array>
     */
    public function processHello(array $args)
    {
        return ['CHALLENGE', (object) ['challenge' => new \stdClass(), 'challenge_method' => $this->getMethodName()]];
    }

    public function processAuthenticate($signature, $extra = null)
    {
        try {
            $jwt = JWT::decode($signature, $this->jwtKey, ['HS256']);

            return ['SUCCESS', (object) ['authid' => $jwt->authid]];
        } catch (\Exception $e) {
            return ['FAILURE'];
        }
    }

    public function getCounter($key, $default = null)
    {
        if ($key === null) {
            return $this->counters;
        }

        return array_get($this->counters, $key, $default);
    }

    public function setCounter($key, $value)
    {
        if ($key === 0) {
            $this->counters = $value;
        } else {
            array_set($this->counters, $key, $value);
        }

        return $this;
    }

    public function onMessage(TransportInterface $transport, Message $msg)
    {
        parent::onMessage($transport, $msg);
        $this->_closeDb();
    }

    public function onOpen(TransportInterface $transport)
    {
        parent::onOpen($transport);
    }

    protected function _closeDb()
    {
        $this->container->get('doctrine')->getConnection()->close();
    }
}
