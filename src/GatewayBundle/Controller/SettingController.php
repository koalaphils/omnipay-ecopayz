<?php

namespace GatewayBundle\Controller;

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
        $statuses = $this->getSettingManager()->getSetting('transaction.status');

        $_statuses = array_dot(array_get($request->get('TransactionSetting', []), 'statuses', []));
        foreach ($_statuses as $key => $dot) {
            array_set($statuses, $key, $dot);
        }

        $form = $this->createForm(TransactionSettingType::class, ['statuses' => $statuses]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = array_get($form->getData(), 'statuses', []);
            $this->getSettingManager()->saveSetting('transaction.status', $data);

            $this->getSession()->getFlashBag()->add('notifications', [
                'title' => $this->getTranslator()->trans('notification.setting.transaction.title', [], 'TransactionBundle'),
                'message' => $this->getTranslator()->trans('notification.setting.transaction.message', [], 'TransactionBundle'),
            ]);

            return $this->redirectToRoute('transaction.setting.transaction_page');
        }

        return $this->render('TransactionBundle:Setting:index.html.twig', [
            'form' => $form->createView(),
        ]);
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
