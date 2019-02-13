<?php

namespace BrokerageBundle\Component;

class BrokerageMembers extends BrokerageApiComponent
{
    private $getMemberToFixPath = '/members-to-fix';
    private $getAllMembersPath = '/customer/all';
    private $getMemberPath = '/customer/{id}';

    public function getMemberToFix(\DateTimeInterface $dwlDate, int $offset = 0, int $limit = 20): array
    {
        return json_decode($this->get($this->getMemberToFixPath, [
            'date' => $dwlDate->format('Y-m-d'),
            'offset' => $offset,
            'limit' => $limit,
        ])->getBody(), true);
    }

    public function getAllMembers(\DateTimeInterface $date): array
    {
        return json_decode($this->get($this->getAllMembersPath, [
            'date' => $date->format('Y-m-d')
        ])->getBody(), true);
    }

    public function getMember(int $syncId, \DateTimeInterface $date): array
    {
        $path = str_replace('{id}', $syncId, $this->getMemberPath);

        return \GuzzleHttp\json_decode($this->get($path, ['date' => $date->format('Y-m-d')])->getBody(), true);
    }
}
