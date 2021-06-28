<?php

namespace TransactionBundle\Controller;

use AppBundle\Controller\AbstractController;
use TransactionBundle\Form\TransactionSettingType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of SettingController.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class SettingController extends AbstractController
{
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_SETTING_PROCESS']);
        $statuses = $this->getSettingManager()->getSetting('transaction.status');

        $_statuses = array_dot(array_get($request->get('TransactionSetting', []), 'statuses', []));
        foreach ($_statuses as $key => $dot) {
            array_set($statuses, $key, $dot);
        }

        $paymentGateway = $this->getSettingManager()->getSetting('transaction.paymentGateway');
        $transactionStart = $this->getSettingManager()->getSetting('transaction.start');

        $form = $this->createForm(TransactionSettingType::class, [
            'statuses' => $statuses,
            'paymentGateway' => $paymentGateway,
            'transactionStart' => $transactionStart,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $statuses = array_get($form->getData(), 'statuses', []);
            $statuses = $this->removeEmptyStatusActions($statuses);
            $gateway = array_get($form->getData(), 'paymentGateway', 'customer-level');
            $transactionAdminStart = $form->get('transactionAdminStart')->getData();
            $transactionCustomerStart = $form->get('transactionCustomerStart')->getData();
            $this->getSettingManager()->saveSetting('transaction.status', $statuses);
            $this->getSettingManager()->saveSetting('transaction.paymentGateway', $gateway);
            $this->getSettingManager()->saveSetting('transaction.start', [
                'customer' => $transactionCustomerStart,
                'admin' => $transactionAdminStart,
            ]);

            $this->getSession()->getFlashBag()->add(
                'notifications',
                [
                    'title' => $this->getTranslator()->trans(
                        'notification.setting.transaction.title',
                        [],
                        'TransactionBundle'
                    ),
                    'message' => $this->getTranslator()->trans(
                        'notification.setting.transaction.message',
                        [],
                        'TransactionBundle'
                    ),
                ]
            );

            return $this->redirectToRoute('transaction.setting.transaction_page');
        }

        return $this->render('TransactionBundle:Setting:index.html.twig', ['form' => $form->createView(), ]);
    }

    private function removeEmptyStatusActions($statuses)
    {
        foreach ($statuses as $key => $status) {
            foreach ($status['actions'] as $statusActionKey => $statusAction) {
                if (empty(trim($statusAction['class'])) && empty(trim($statusAction['label'])) && empty(trim($statusAction['status']))) {
                    unset($statuses[$key]['actions'][$statusActionKey]);
                }
            }
        }
        return $statuses;
    }

    protected function getManager()
    {
    }

    /**
     * Get Setting Manager.
     *
     * @return \AppBundle\Manager\SettingManager
     */
    protected function getSettingManager()
    {
        return $this->getContainer()->get('app.setting_manager');
    }
}
