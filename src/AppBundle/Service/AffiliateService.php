<?php

namespace AppBundle\Service;

use ApiBundle\Service\JWTGeneratorService;

class AffiliateService
{
    private $http;
    private $jwtGenerator;

    public function __construct(HttpService $http, JWTGeneratorService $jwtGenerator)
    {
        $this->http = $http;
        $this->jwtGenerator = $jwtGenerator;
    }

    private function getBearer()
    {
        $jwt = $this->jwtGenerator->generate([]);

        return 'Bearer ' . $jwt;
    }

    public function getAffiliate(int $affiliateUserId)
    {
        $url = sprintf('/api/v1/affiliate/%d', $affiliateUserId);

        $response = $this->http->get($url,[
            'headers' => [
                'Authorization' => $this->getBearer(),
             ]
        ]);

        return $response;
    }

    public function getAffiliates(array $params)
    {
        $url = '/api/v1/affiliate?name=%s';
        $url = sprintf(
            $url,
            array_has($params, 'search') ? $params['search'] : ''
        );

        $response = $this->http->get($url, [
            'headers' => [
                'Authorization' => $this->getBearer(),
             ]
        ]);

        return $response;
    }

    public function addMember($memberUserId, $affiliateUserId)
    {
        $this->http->post("/api/v1/affiliate/" . $affiliateUserId . "/member", [
            'json' => [
                'member_user_id' => $memberUserId
            ],
            'headers' => [
                'Authorization' => $this->getBearer(),
             ]
        ]);
    }

    public function removeMember($memberUserId, $affiliateUserId)
    {
        $this->http->delete("/api/v1/affiliate/" . $affiliateUserId . "/member/" . $memberUserId, [
            'headers' => [
                'Authorization' => $this->getBearer(),
            ]
        ]);
    }

    public function linkMemberViaReferralCode($referralCode, $memberUserId)
    {
        return $this->http->post("/api/v1/affiliate/link-member", [
            'json' => [
                'referral_code' => $referralCode,
                'member_user_id' => $memberUserId
            ],
            'headers' =>  [
                'Authorization' => $this->getBearer(),
            ]
        ]);
    }

    public function createAffiliate(array $payload)
    {
        $this->http->post("/api/v1/affiliate", [
            'json' => $payload,
            'headers' => [
               'Authorization' => $this->getBearer(),
            ]
        ]);
    }
}