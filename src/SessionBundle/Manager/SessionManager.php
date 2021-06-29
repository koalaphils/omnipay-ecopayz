<?php

namespace SessionBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\Session;
use Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder;

class SessionManager extends AbstractManager
{
    public function tokenIsMatch($token, $sessionToken)
    {
        return $this->_encodeToken($token) === $sessionToken;
    }

    public function remove($entity)
    {
        return $this->getRepository()->remove($entity);
    }

    public function findBySessionToken($key)
    {
        return $this->getRepository()->findBySessionToken($this->_encodeToken($key));
    }

    public function create($params)
    {
        $session = new Session();
        $session->setSessionId($params['sessionId']);
        $session->setKey($this->_encodeToken($params['key']));
        $session->setUser($params['user']);
        $session->setDetails([]);

        $this->getRepository()->save($session);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     *
     * @return string url logout
     */
    public function logout()
    {
        return $this->getRouter()->generate('app.logout');
    }

    /**
     * @return Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage
     */
    protected function getTokenStorage()
    {
        return $this->get('security.token_storage');
    }

    /**
     * Get session repository.
     *
     * @return \DbBundle\Repository\SessionRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Session');
    }

    private function _encodeToken($token)
    {
        $encoder = new Pbkdf2PasswordEncoder();
        $sessionToken = $encoder->encodePassword($token, 'session_token');

        return $sessionToken;
    }
}
