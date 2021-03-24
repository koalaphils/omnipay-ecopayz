<?php

namespace GatewayBundle\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use AppBundle\Manager\SettingManager;

/**
 * Description of TransactionValidator.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class GatewayValidator
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    /**
     * @var \AppBundle\Manager\SettingManager
     */
    private $settingManager;

    public function __construct(Registry $doctrine, SettingManager $settingManager)
    {
        $this->doctrine = $doctrine;
        $this->settingManager = $settingManager;
    }

    /**
     * @param \DbBundle\Entity\Gateway  $object
     * @param ExecutionContextInterface $context
     */
    public static function validate($object, ExecutionContextInterface $context)
    {
        $levels = $object->getLevels();

        $gateways = self::getGatewayRepository()->findByLevels($levels, \Doctrine\ORM\Query::HYDRATE_ARRAY);
        if (count($gateways) > 1) {
            $context->buildViolation('One or more levels that was selected was already used by other gateway')->atPath('levels')->addViolation();
        } elseif (count($gateways) == 1 && $gateways[0]['id'] != $object->getId()) {
            $context->buildViolation('One or more levels that was selected was already used by other gateway')->atPath('levels')->addViolation();
        }
    }

    public static function getTypes()
    {
        $type = [];
        foreach (self::getSettingManager()->getSetting('paymentOptions', []) as $paymentOption) {
            $type[] = $paymentOption['code'];
        }

        return $type;
    }

    /**
     * @return \DbBundle\Repository\GatewayRepository
     */
    public static function getGatewayRepository()
    {
        return self::getDoctrine()->getManager()->getRepository('DbBundle:Gateway');
    }

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Registry;
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    public static function getDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * Get Setting Manager.
     *
     * @return \AppBundle\Manager\SettingManager
     */
    public static function getSettingManager()
    {
        return $this->settingManager;
    }
}
