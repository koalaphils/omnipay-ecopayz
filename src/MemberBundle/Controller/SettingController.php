<?php

declare(strict_types = 1);

namespace MemberBundle\Controller;

use AppBundle\Manager\SettingManager;
use MemberBundle\Request\ReferralSettingRequest;
use MemberBundle\RequestHandler\SettingRequestHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingController extends AbstractController
{
    public function referralSettingAction(Request $request, SettingManager $settingManager, SettingRequestHandler $handler): Response
    {
        $data = $settingManager->getSetting('referral.cookie.expiration') / 86400;

        $referralSetting = new ReferralSettingRequest();
        $referralSetting->setCookieExpiration((int) $data);

        $formBuilder = $this->createFormBuilder($referralSetting, ['data_class' => ReferralSettingRequest::class])
            ->add('cookieExpiration', NumberType::class, ['label' => 'Referral Site Cookie Expiration'])
            ->add('saveBtn', SubmitType::class, ['label' => 'Save'])
        ;
        $form = $formBuilder->getForm();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $handler->handleReferralSetting($form->getData());
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }
        }

        return $this->render('@Member/Setting/referral-setting.html.twig', ['form' => $form->createView()]);
    }
}