<?php

declare(strict_types = 1);

namespace SessionBundle\Controller;

use AppBundle\Manager\SettingManager;
use SessionBundle\Form\SettingForm;
use SessionBundle\Model\SettingModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingController extends AbstractController
{
    public function sessionPageAction(SettingManager $settingManager): Response
    {
        $sessionSetting = SettingModel::fromArray($settingManager->getSetting('session'));

        $settingForm = $this->createForm(SettingForm::class, $sessionSetting, [
            'action' => $this->generateUrl('session_setting_save')
        ]);

        return $this->render('SessionBundle:Setting:session.html.twig', [
            'form' => $settingForm->createView()
        ]);
    }

    public function sessionSaveAction(Request $request, SettingManager $settingManager): JsonResponse
    {
        $sessionSetting = SettingModel::fromArray($settingManager->getSetting('session'));
        $settingForm = $this->createForm(SettingForm::class, $sessionSetting);

        $settingForm->handleRequest($request);
        if ($settingForm->isSubmitted() && $settingForm->isValid()) {
            $settingManager->saveSetting('session.timeout', $settingForm->getData()->getSessionTimeout());
            $settingManager->saveSetting('session.pinnacle_timeout', $settingForm->getData()->getPinnacleTimeout());
        } else {
            return $this->json(['success' => false], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['success' => true]);
    }
}