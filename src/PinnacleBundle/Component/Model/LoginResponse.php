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
     * @var array
     */
    private $data;

    public static function create(array $data): self
    {
        $instance = new static();
        $instance->data = $data;

        return $instance;
    }

    public function loginUrl(): string
    {
        return $this->data['loginUrl'];
    }

    public function userCode(): string
    {
        return $this->data['userCode'];
    }

    public function loginId(): string
    {
        return $this->data['loginId'];
    }

    public function token(): string
    {
        return $this->data['token'];
    }

    public function updatedDate(): int
    {
        return $this->data['updatedDate'];
    }

    public function updatedDateAsDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->getUpdatedDate(), new \DateTimeZone('UTC'));
    }

    public function toArray(): array
    {
        return $this->data;
    }

    private function __construct()
    {
    }
}