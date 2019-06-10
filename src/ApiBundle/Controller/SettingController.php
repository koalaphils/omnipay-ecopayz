<?php

declare(strict_types = 1);

namespace ApiBundle\Controller;

use AppBundle\Manager\SettingManager;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

class SettingController extends AbstractController
{
    /**
     * @ApiDoc(
     *     section="Application",
     *     description="Setting/Configurations",
     *     views={"piwi"},
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function getSettingAction(Request $request, SettingManager $settingManager): View
    {
        $confirmations = $settingManager->getSetting('bitcoin.confirmations');

        $settings = [
            'piwi247.session' => $settingManager->getSetting('piwi247.session'),
            'bitcoin.setting' => $settingManager->getSetting('bitcoin.setting'),
            'transaction.validate' => $settingManager->getSetting('transaction.validate'),
            'pinnacle' => $settingManager->getSetting('pinnacle'),
            'session' => $settingManager->getSetting('session'),
            'bitcoin.confirmations' => $confirmations,
            'bitcoin.max_confirmations' => count($confirmations) - 1,
            'referral.expiration' => $settingManager->getSetting('referral.cookie.expiration'),
        ];

        return $this->view([
            'error' => false,
            'message' => '',
            'status' => 200,
            'data' => $settings,
        ], 200);
    }
}