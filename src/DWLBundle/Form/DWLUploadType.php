<?php

namespace DWLBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use AppBundle\Form\Type as CType;

/**
 * Description of DWLType.
 *
 * @author cnonog
 */
class DWLUploadType extends AbstractType
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    private $router;

    public function __construct(Registry $doctrine, Router $router)
    {
        $this->doctrine = $doctrine;
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', CType\Select2Type::class, [
                'label' => 'fields.product',
                'required' => true,
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('product.list_search_without_betadmin'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 1,
                ],
                'placeholder' => 'Select Product',
                'translation_domain' => 'DWLBundle',
            ])
            ->add('currency', CType\Select2Type::class, [
                'label' => 'fields.currency',
                'required' => true,
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('currency.list_search'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 1,
                ],
                'placeholder' => 'Select Currency',
                'translation_domain' => 'DWLBundle',
            ])
            ->add('date', Type\DateType::class, [
                'label' => 'fields.date',
                'translation_domain' => 'DWLBundle',
                'widget' => 'single_text', 'format' => 'MM/dd/yyyy',
            ])
            ->add('file', Type\FileType::class, [
                'label' => 'fields.file',
                'translation_domain' => 'DWLBundle',
                'mapped' => false,
                'constraints' => [
                    new Constraints\NotBlank(['groups' => ['default', 'newVersion']]),
                    new Constraints\File([
                        'groups' => ['default', 'newVersion'],
                        'mimeTypes' => ['text/csv', 'application/vnd.ms-excel', 'application/csv', 'text/plain'],
                    ]),
                ],
            ])
            ->add('_v', Type\HiddenType::class, ['property_path' => 'updatedAt'])
            ->add('save', Type\SubmitType::class, [
                'label' => 'form.save',
                'translation_domain' => 'AppBundle',
                'attr' => ['class' => 'btn-success'],
            ])
        ;

        $builder->get('_v')->addModelTransformer(new CallbackTransformer(
            function ($data) {
                if ($data instanceof \DateTime) {
                    $data = $data->format('Y-m-d H:i:s');
                }

                return base64_encode($data);
            },
            function ($data) {
                if (is_string($data)) {
                    $data = base64_decode($data);
                    $data = \DateTime::createFromFormat('Y-m-d H:i:s', $data);
                }

                return $data;
            }
        ));

        $builder->get('product')->addModelTransformer(new CallbackTransformer(
            function ($product) {
                $data = $product;
                if ($product instanceof \DbBundle\Entity\Product) {
                    $data = [
                        'id' => $product->getId(),
                        'text' => $product->getName(),
                    ];
                } elseif (is_array($product)) {
                    return [$product];
                }

                return [[$data]];
            },
            function ($product) {
                if (!($product instanceof \DbBundle\Entity\Product) && !is_null($product)) {
                    $product = $this->getProductRepository()->find($product);
                }

                return $product;
            }
        ));

        $builder->get('currency')->addModelTransformer(new CallbackTransformer(
            function ($currency) {
                $data = $currency;
                if ($currency instanceof \DbBundle\Entity\Currency) {
                    $data = [
                        'id' => $currency->getId(),
                        'text' => $currency->getName(),
                    ];
                } elseif (is_array($currency)) {
                    return [$currency];
                }

                return [[$data]];
            },
            function ($currency) {
                if (!($currency instanceof \DbBundle\Entity\Currency) && !is_null($currency)) {
                    $currency = $this->getCurrencyRepository()->find($currency);
                }

                return $currency;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'DbBundle\Entity\DWL',
                'csrf_protection' => true,
                'csrf_field_name' => '_token',
                'csrf_token_id' => 'dwl',
                'validation_groups' => 'default',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'DWLUpload';
    }

    /**
     * Get product repository.
     *
     * @return \DbBundle\Repository\ProductRepository
     */
    public function getProductRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Product');
    }

    /**
     * Get currency repository.
     *
     * @return \DbBundle\Repository\CurrencyRepository
     */
    public function getCurrencyRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Currency');
    }
}
