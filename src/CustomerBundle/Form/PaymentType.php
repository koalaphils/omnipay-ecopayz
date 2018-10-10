<?php

namespace CustomerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Manager\SettingManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\Bundle\DoctrineBundle\Registry;
use AppBundle\Form\Type as CType;

/**
 * Description of SecurityType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class PaymentType extends AbstractType
{
    /**
     * @var \AppBundle\Manager\SettingManager
     */
    protected $settingManager;
    protected $doctrine;

    public function __construct(SettingManager $settingManager, Registry $doctirne)
    {
        $this->settingManager = $settingManager;
        $this->doctrine = $doctirne;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', Type\ChoiceType::class, [
            'label' => 'fields.payment.type',
            'translation_domain' => 'CustomerBundle',
            'choices' => $this->getPaymentOptionTypes(),
            //'choices_as_values' => true,
        ]);

        $builder->add('isActive', CType\SwitchType::class, [
            'label' => 'fields.payment.isActive',
            'required' => false,
            'translation_domain' => 'CustomerBundle',
        ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => 'form.save',
            'translation_domain' => 'AppBundle',
            'attr' => [
                'class' => 'btn-success',
            ],
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $customerPaymentOption = $event->getData();
            $form = $event->getForm();
            if ($customerPaymentOption) {
                $customerPaymentOption->clearCustomFields();
            }
            foreach ($form->getExtraData() as $field => $value) {
                $customerPaymentOption->addField($field, $value);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\CustomerPaymentOption',
            'validation_groups' => 'default',
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'CustomerBank';
    }

    protected function getPaymentOptionTypes()
    {
        $paymentOptions = [
            '' => '',
        ];

        foreach ($this->getPaymentOptionRepository()->filter() as $paymentOption) {
            if ($paymentOption->getIsActive()) {
                $paymentOptions[$paymentOption->getName()] = $paymentOption->getCode();
            }
        }

        return $paymentOptions;
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
}
