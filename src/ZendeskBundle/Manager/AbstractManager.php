<?php

namespace ZendeskBundle\Manager;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Description of AbstractManager.
 *
 * @author cnonog
 */
class AbstractManager
{
    use ContainerAwareTrait;

    public function getContainer()
    {
        return $this->container;
    }

    public function get($id)
    {
        return $this->getContainer()->get($id);
    }

    /**
     * Get Doctrine.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    public function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * Get Router.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    public function getRouter()
    {
        return $this->getContainer()->get('router');
    }

    /**
     * Get zendesk api manager.
     *
     * @return \Zendesk\API\HttpClient
     */
    public function getZendeskAPI()
    {
        return $this->getContainer()->get('zendesk.api_manager')->getAPI();
    }

    public function getLastResponseCode()
    {
        return $this->getZendeskAPI()->getDebug()->lastResponseCode;
    }

    /**
     * Get translator.
     *
     * @return \Symfony\Component\Translation\DataCollectorTranslator
     */
    protected function getTranslator()
    {
        return $this->getContainer()->get('translator');
    }
}
