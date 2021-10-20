<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component;

use PinnacleBundle\Component\Model\Player;

class PlayerComponent extends PinnacleComponent
{
    private const CREATE_PLAYER_PATH = '/player/create';
    private const GET_PLAYER_PATH = '/player/info';
    private const UPDATE_STATUS_PATH = '/player/update-status';

    public function createPlayer(string $loginId = '', string $agentCode = '')
    {
        $input = [];
        if ($loginId !== '') {
            $input['loginId'] = $loginId;
        }

        if ($agentCode !== '') {
            $input['agentCode'] = $agentCode;
        }

        $data = $this->get(self::CREATE_PLAYER_PATH, $input);

        if (array_has($data, 'userCode')) {
            return $this->getPlayer($data['userCode']);
        }
    }

    public function getPlayer(string $userCode): Player
    {
        $data = $this->get(self::GET_PLAYER_PATH, ['userCode' => $userCode]);

        return Player::create($data);
    }

    public function updateStatus(string $userCode, string $activeStatus)
    {
       $this->get(self::UPDATE_STATUS_PATH, ['userCode' => $userCode, 'status' => $activeStatus]);
    }
}
