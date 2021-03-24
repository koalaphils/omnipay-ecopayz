<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component;

use PinnacleBundle\Component\Model\LoginResponse;

class AuthComponent extends PinnacleComponent
{
    private const LOGIN_PATH = '/player/login';
    private const LOGOUT_PATH = '/player/logout';

    public function login(string $userCode, ?string $locale = null): LoginResponse
    {
        $info = ['userCode' => $userCode];
        if ($locale !== null) {
            $info['locale'] = $locale;
        }
        $data = $this->get(self::LOGIN_PATH, $info);

        return LoginResponse::create($data);
    }

    public function logout(string $userCode, ?string $token = null): bool
    {
        $data = $this->get(self::LOGOUT_PATH, ['userCode' => $userCode], ['headers' => ['token' => $token]]);

        $status = $data['status'] ?? 'unsuccessfull';

        return $status === 'successful';
    }
}
