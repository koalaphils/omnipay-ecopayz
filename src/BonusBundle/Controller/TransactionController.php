<?php

namespace BonusBundle\Controller;

use AppBundle\Controller\AbstractController;
use DbBundle\Entity\Bonus;

class TransactionController extends AbstractController
{
    public function indexAction()
    {
        $statuses = $this->getContainer()->get('app.setting_manager')->getSetting('transaction.status');
        $this->getSettingRepository()->updateSetting('bonus', 'counter', 0);

        return $this->render('BonusBundle:Transaction:index.html.twig', ['statuses' => $statuses]);
    }

    /**
     * @return \BonusBundle\Manager\BonusManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('bonus.manager');
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
