<?php

namespace AppBundle\Service;

class AffiliateService
{
    private $http;

    public function __construct(HttpService $http)
    {
        $this->http = $http;
    }

    public function getAffiliates(array $params)
    {
        $url = '/api/v1/affiliate?name=%s';
        $url = sprintf(
            $url,
            array_has($params, 'search') ? $params['search'] : ''
        );

        $response = $this->http->get($url, []);

        return $response;
    }

    public function mapMemberToAffiliate($memberUserId, $affiliateUserId)
    {
        $this->http->post("/api/v1/affiliate/" . $affiliateUserId . "/map-member", [
            'json' => [
                'member_user_id' => $memberUserId
            ],
            'headers' => []
        ]);
    }

    public function createAffiliate(array $payload)
    {
        $this->http->post("/api/v1/affiliate", [
            'json' => $payload,
            'headers' => []
        ]);
    }
}