<?php
/**
 * Created by PhpStorm.
 * User: cydrick
 * Date: 3/21/19
 * Time: 12:58 PM
 */

namespace PinnacleBundle\Component\Model;


class LoginResponse
{
    /**
     * @var string
     */
    private $loginUrl;

    /**
     * @var string
     */
    private $userCode;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $loginId;

    /**
     * @var \DateTimeImmutable
     */
    private $updateDate;

    public static function create(array $data): self
    {
        $instance = new static();
        $instance->loginId = $data['loginId'];
        $instance->userCode = $data['userCode'];
        $instance->token = $data['token'];
        $instance->loginUrl = $data['loginUrl'];
        $instance->updateDate = $data['updateDate'];

        return $instance;
    }

    public function loginUrl(): string
    {
        return $this->loginUrl;
    }

    public function userCode(): string
    {
        return $this->userCode;
    }

    public function loginId(): string
    {
        return $this->loginId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function updateDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->updateDate, new \DateTimeZone('Etc/GMT+4'));
    }

    public function toArray(): array
    {
        return [
            'user_code' => $this->userCode,
            'login_id' => $this->loginId,
            'token' => $this->token,
            'login_url' => $this->loginUrl,
            'updated_date' => $this->updateDate()->format('c'),
        ];
    }

    private function __construct()
    {
    }
}