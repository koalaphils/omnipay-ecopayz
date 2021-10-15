<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\MaintenanceType;
use AppBundle\Form\SchedulerType;
use AppBundle\Form\PaymentOptionsType;
use Codeception\Util\HttpCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use DbBundle\Entity\Setting;
use Exception;

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

        $form = $this->createForm(MaintenanceType::class, $maintenance);
        $data = $this->getManager()->getSetting('system.maintenance');
        dump($data);

        return $this->render('AppBundle:Setting:maintenance.html.twig', ['data' => $data, 'form' => $form->createView()]);
    }

    public function paymentOptionAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_VIEW_PAYMENTOPTIONS']);

        $paymentOptions = [];
        $paymentOptions['paymentOptions'] = $this->getManager()->getSetting('paymentOptions');
        $form = $this->createForm(PaymentOptionsType::class, $paymentOptions);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $_paymentOptions = [];
            foreach ($data['paymentOptions'] as $option) {
                $_fields = [];
                foreach ($option['fields'] as $key => $field) {
                    $_fields[$field['code']] = $field;
                }
                $option['fields'] = $_fields;
                $_paymentOptions[$option['code']] = $option;
            }

            $this->getManager()->saveSetting('paymentOptions', $_paymentOptions);

            $this->getSession()->getFlashBag()->add(
                'notifications',
                [
                    'title' => $this->getTranslator()->trans('notification.paymentoption.title', [], 'AppBundle'),
                    'message' => $this->getTranslator()->trans('notification.paymentoption.message', [], 'AppBundle'),
                ]
            );

            return $this->redirectToRoute('app.setting.paymentoptions_page');
        }

        return $this->render('AppBundle:Setting:paymentOption.html.twig', ['form' => $form->createView()]);
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

    public function saveMaintenanceAction(Request $request): JsonResponse 
    {
        $this->denyAccessUnlessGranted(['ROLE_MAINTENANCE']);

        $payload = $request->request->all();
        $isSettingActiveTab = false;

        if (isset($payload['value'])) {
            $payload['value'] = filter_var($payload['value'], FILTER_VALIDATE_BOOLEAN);
            $status = $payload['value'] ? 'Activated' : 'Deactivated';
            $message = ['message' => ucfirst($payload['type']) . ' maintenance for '. $payload['key'] .' has been ' . $status];
        }
        
        if (isset($payload['is_default'])) {
            $isSettingActiveTab = true;
            $payload['is_default'] = filter_var($payload['is_default'], FILTER_VALIDATE_BOOLEAN);
            $message = ['message' => ucfirst($payload['key']) .' has been set as default active tab.'];
        }

        try {
            $this->getManager()->updateMaintenanceSetting($payload, $isSettingActiveTab);
        } catch (Exception $exception) {
            throw $exception;
        } 

        return new JsonResponse($message, Response::HTTP_OK);
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

        /**
     * Get setting repository.
     *
     * @return \DbBundle\Repository\SettingRepository
     */
    protected function getSettingRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Setting');
    }
}
