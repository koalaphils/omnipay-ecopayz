<?php

namespace GatewayBundle\Form;

use AppBundle\Form\Type as CType;
use AppBundle\Manager\SettingManager;
use AppBundle\Service\PaymentOptionService;
use DbBundle\Entity\Gateway;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class GatewayType extends AbstractType
{
    private $doctrine;
    private $settingManager;
    private $translator;
    private $router;
    private $authorizationChecker;
    private $poService;

    public function __construct(
        Registry $doctrine,
        SettingManager $settingManager,
        Router $router,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
	    PaymentOptionService $poService
    ) {
        $this->doctrine = $doctrine;
        $this->settingManager = $settingManager;
        $this->router = $router;
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->poService = $poService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $maxLevel = $this->getSettingManager()->getSetting('level.max');
        $levels = [];
        for ($i = 1; $i <= $maxLevel; ++$i) {
            $levels[$this->getTranslator()->trans('fields.level', [], 'CustomerBundle') . " $i"] = $i;
        }

        $builder
            ->add('name', Type\TextType::class, [
                'label' => 'fields.name',
                'required' => true,
                'translation_domain' => 'GatewayBundle',
            ])
            ->add('currency', CType\Select2Type::class, [
                'label' => 'fields.currency',
                'translation_domain' => 'GatewayBundle',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('currency.list_search'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                ],
                'required' => false,
            ])
            ->add('balance', Type\NumberType::class, [
                'label' => 'fields.balance',
                'translation_domain' => 'GatewayBundle',
                'required' => true,
                'scale' => 2,
            ])
            ->add('paymentOption', Type\ChoiceType::class, [
                'choices' => $this->getPaymentOptionTypes(),
                'choices_as_values' => true,
                'label' => 'fields.paymentOption',
                'translation_domain' => 'GatewayBundle',
                'required' => true,
            ])
            ->add('isActive', CType\SwitchType::class, [
                'label' => 'fields.isActive',
                'required' => false,
                'translation_domain' => 'GatewayBundle',
            ])
            ->add('details', Type\FormType::class)
            ->add('save', Type\SubmitType::class, [
                'label' => 'form.save',
                'translation_domain' => 'AppBundle',
            ]);

        $builder->get('details')
            ->add('methods', Type\CollectionType::class, [
                'entry_type' => MethodSettingType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ])
        ;

        if (PaymentOptionService::ECOPAYZ === $options['payment_option']) {
            $builder->get('details')->add('config', EcopayzConfigType::class);
        } else {
            $builder->get('details')->add('config', Type\FormType::class);
        }

        $builder->get('currency')->addViewTransformer(new CallbackTransformer(
            function ($currency) {
                if ($currency && !is_array($currency) && $currency->getId()) {
                    $currency = $this->getCurrencyRepository()->find($currency);

                    return [['id' => $currency->getId(), 'text' => $currency->getName()]];
                }

                return $currency;
            },
            function ($currency) {
                if ($currency) {
                    return $this->getCurrencyRepository()->find($currency);
                }

                return null;
            }
        ));

        $builder->get('currency')->addModelTransformer(new CallbackTransformer(
            function ($currency) {
                if ($currency && $currency->getId()) {
                    $currency = $this->getCurrencyRepository()->find($currency);

                    return [['id' => $currency->getId(), 'text' => $currency->getName()]];
                }

                return $currency;
            },
            function ($currency) {
                if ($currency) {
                    return $this->getCurrencyRepository()->find($currency);
                }

                return null;
            }
        ));

        if (!$this->authorizationChecker->isGranted(['ROLE_GATEWAY_CHANGE_STATUS'])) {
            $builder->remove('isActive');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Gateway::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'gateway',
            'validation_groups' => 'default',
            'payment_option' => ''
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Gateway';
    }

    /**
     * @return \DbBundle\Repository\CurrencyRepository
     */
    public function getCurrencyRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Currency');
    }

    protected function getPaymentOptionTypes()
    {
	    return array_reduce($this->poService->getAllPaymentOptions(), function ($acc, $po) {
		    $acc[$po['name']] = $po['code'];

		    return $acc;
	    }, []);
    }

    protected function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->doctrine->getRepository(\DbBundle\Entity\PaymentOption::class);
    }

    /**
     * Get Setting Manager.
     *
     * @return \AppBundle\Manager\SettingManager
     */
    protected function getSettingManager()
    {
        return $this->settingManager;
    }

    /**
     * Get translator.
     *
     * @return \Symfony\Component\Translation\DataCollectorTranslator
     */
    protected function getTranslator()
    {
        return $this->translator;
    }
}
