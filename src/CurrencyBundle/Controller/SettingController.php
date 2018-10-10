<?php

namespace CurrencyBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use CurrencyBundle\Form\CurrencySettingType;

class SettingController extends AbstractController
{
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CURRENCY_CHANGE_BASE']);
        $setting = $this->getManager()->getSetting('currency.base', null);
        if (!is_null($setting)) {
            $setting = $this->getCurrencyRepository()->find($setting);
        }

        $form = $this->createForm(CurrencySettingType::class, ['baseCurrency' => $setting]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = array_get($form->getData(), 'baseCurrency');
            $this->getManager()->saveSetting('currency.base', $data->getId());
            $data->setRate(1);

            $this->getCurrencyRepository()->save($data);

            $this->getSession()->getFlashBag()->add('notifications', [
                'title' => $this->getTranslator()->trans('notification.setting.currency.title', [], 'CurrencyBundle'),
                'message' => $this->getTranslator()->trans('notification.setting.currency.message', [], 'CurrencyBundle'),
            ]);

            return $this->redirectToRoute('currency.setting.currency_page');
        }

        return $this->render('CurrencyBundle:Setting:index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Get currency repository.
     *
     * @return \DbBundle\Repository\CurrencyRepository
     */
    public function getCurrencyRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('DbBundle:Currency');
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
