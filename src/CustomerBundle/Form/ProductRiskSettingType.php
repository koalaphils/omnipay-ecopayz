<?php

namespace CustomerBundle\Form;

use AppBundle\Form\Type as CType;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

use DbBundle\Entity\Product;

class ProductRiskSettingType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManager $entityManager) 
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product',Type\HiddenType::class, [])
            ->add('riskSetting',Type\HiddenType::class, [])
            ->add('riskSettingPercentage', Type\NumberType::class, [
                'scale' => 2,
                'rounding_mode' => NumberToLocalizedStringTransformer::ROUND_DOWN
            ])
        ;

        $entityManager = $this->entityManager;

        //TODO: Create a generic transformer
        $builder->get('product')
            ->addModelTransformer(new CallbackTransformer(
                function ($product) {
                    if ($product) {
                        return $product->getId();
                    }

                   return '';
                },
                function ($productId) use ($entityManager) {
                    $product = $this->entityManager->getRepository(Product::class)->find($productId);

                    if (!$product) {
                        throw new TransformationFailedException();
                    }

                    return $product;
                }
            ))
        ;
        
        $builder->get('riskSetting')
            ->addModelTransformer(new CallbackTransformer(
                function ($riskSetting) {
                    if ($riskSetting) {
                        return $riskSetting->getId();
                    }

                   return '';
                },
                function ($riskSettingId) use ($entityManager) {
                    $riskSetting = $this->entityManager->getRepository(\DbBundle\Entity\RiskSetting::class)->find($riskSettingId);

                    if (!$riskSetting) {
                        throw new TransformationFailedException();
                    }

                    return $riskSetting;
                }
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\ProductRiskSetting',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'productRiskSetting',
            'validation_groups' => 'Default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'productRiskSetting';
    }
}
