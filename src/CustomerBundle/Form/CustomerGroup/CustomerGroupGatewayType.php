<?php

namespace CustomerBundle\Form\CustomerGroup;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;
use DbBundle\Entity\Gateway;

/**
 * Payment Gateway
 */
class CustomerGroupGatewayType extends AbstractType
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gateway', CType\Select2Type::class, [])
            ->add('conditions', Type\TextareaType::class, [])
        ;

        $builder->get('gateway')->addModelTransformer(new CallbackTransformer(
            function ($data) {
                if ($data instanceof Gateway) {
                    return $data->getId();
                }

                return $data;
            },
            function ($data) {
                return $this->getGatewayRepository()->find($data);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\CustomerGroupGateway',
            'validation_groups' => 'Default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'PaymentGateway';
    }

    private function getGatewayRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Gateway');
    }
}
