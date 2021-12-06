<?php

namespace AppBundle\Service;

use DbBundle\Repository\UserRepository;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class JWTService
{
	private $jwtKey;
	private $userRepository;
	private $tokenStorage;

	public function __construct(string $jwtKey, UserRepository $userRepository, TokenStorageInterface $tokenStorage)
	{
		$this->jwtKey = $jwtKey;
		$this->userRepository = $userRepository;
		$this->tokenStorage = $tokenStorage;
	}

	public function getJWT(array $options = [])
	{
		if (isset($options['username'])) {
			$user = $this->userRepository->loadUserByUsername($options['username']);
		} else {
			$user = $this->tokenStorage->getToken()->getUser();
		}

		$payload = [
			'username' => $user->getUserName(),
			'id' => $user->getId(),
			'roles' => $user->getRoles()
		];
		$jwt = JWT::encode($payload, $this->jwtKey);

		return $jwt;
	}
}
