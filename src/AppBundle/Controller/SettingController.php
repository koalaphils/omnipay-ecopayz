<?php

namespace AppBundle\Controller;

use AppBundle\Form\MaintenanceType;
use AppBundle\Form\SchedulerType;
use AppBundle\Form\PaymentOptionsType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use DbBundle\Entity\Setting;

/**
 * Description of SettingController.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class SettingController extends AbstractController
{
    public function maintenanceAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_MAINTENANCE']);

        $maintenance = $this->getManager()->getSetting('maintenance');
        $maintenance['enabled'] = ($maintenance['enabled']) ? true : false;

        $form = $this->createForm(MaintenanceType::class, $maintenance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->getManager()->updateSetting('maintenance.enabled', $data['enabled']);

            $this->getSession()->getFlashBag()->add(
                'notifications',
                [
                    'title' => $this->getTranslator()->trans('notification.setting.title', [], 'AppBundle'),
                    'message' => $this->getTranslator()->trans('notification.setting.message', [], 'AppBundle'),
                ]
            );

            return $this->redirectToRoute('app.setting.maintenance_page');
        }

        return $this->render('AppBundle:Setting:maintenance.html.twig', ['form' => $form->createView()]);
    }

    public function paymentOptionAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_VIEW_PAYMENTOPTIONS']);

        return $this->render('AppBundle:Setting:paymentOption.html.twig');
    }

    public function settingAction(Request $request)
    {
        $setting = $this->getManager()->getSetting($request->get('setting', ''));

        return new JsonResponse($setting);
    }

    public function schedulerAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_SCHEDULER']);
        $scheduler = $this->getManager()->getSetting('scheduler');
        $message = [
            'type' => 'error',
            'title' => $this->getTranslator()->trans('notification.scheduler.title', [], 'AppBundle'),
            'message' => $this->getTranslator()->trans('notification.scheduler.message_failed', [], 'AppBundle'),
        ];

        if (!empty($scheduler[Setting::SCHEDULER_TASK])) {
            $validationGroups = ['default', 'customer'];
            $tasks = $this->getManager()->getItemConfig($scheduler, Setting::SCHEDULER_TASK);

            $formAutoDecline = $this->createForm(SchedulerType::class, $tasks[Setting::TASK_AUTODECLINE], [
                'validation_groups' => $validationGroups,
            ]);
            $formAutoDecline->handleRequest($request);

            if ($formAutoDecline->isSubmitted() && $formAutoDecline->isValid()) {
                $data = $formAutoDecline->getData();

                if ($this->getManager()->hasValidTimeInMinutes($data)) {
                    $tasks[Setting::TASK_AUTODECLINE] = $data;
                    $this->getManager()->saveSetting('scheduler.' . Setting::SCHEDULER_TASK , $tasks);
                    $message = [
                        'title' => $this->getTranslator()->trans('notification.scheduler.title', [], 'AppBundle'),
                        'message' => $this->getTranslator()->trans('notification.scheduler.message', [], 'AppBundle'),
                    ];
                }
                $this->getSession()->getFlashBag()->add('notifications', $message);

                return $this->redirectToRoute('app.setting.scheduler_page');
            }
        }

        return $this->render('AppBundle:Setting:scheduler.html.twig', ['form' => $formAutoDecline->createView()]);
    }

    public function bannerAction(Request $request)
    {
        return $this->render('AppBundle:Setting:banner.html.twig');
    }

    /**
     * Get Setting Manager.
     *
     * @return \AppBundle\Manager\SettingManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('app.setting_manager');
    }
}
