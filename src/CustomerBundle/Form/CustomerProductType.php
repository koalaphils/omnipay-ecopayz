<?php

namespace CustomerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use AppBundle\Form\Type\SwitchType;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\FormInterface;

class CustomerProductType extends AbstractType
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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('customer', HiddenType::class, [
                'label' => 'fields.customerName',
                'required' => true,
                'translation_domain' => 'CustomerProductBundle',
            ])
            ->add('userName', TextType::class, [
                'label' => 'fields.userName',
                'translation_domain' => 'CustomerProductBundle',
                'invalid_message' => 'customerProduct.userName.regex',
                'required' => true,
            ])
            ->add('product', \AppBundle\Form\Type\Select2Type::class, [
                'label' => 'fields.name',
                'translation_domain' => 'ProductBundle',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('product.list_search'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                    'data-id-column' => 'id',
                ],
                'required' => true,
            ])
            ->add('balance', NumberType::class, [
                'label' => 'fields.balance',
                'translation_domain' => 'CustomerProductBundle',
                'required' => true,
                'scale' => 2,
                'invalid_message' => 'customerProduct.balance.type',
                //'data'                  => 0
            ])
            ->add('isActive', SwitchType::class, [
                'label' => 'fields.isActive',
                'required' => false,
                'translation_domain' => 'CustomerProductBundle',
                'data' => true,
            ])
            ->add('saveModal', ButtonType::class, [
                'label' => 'form.save',
                'translation_domain' => 'AppBundle',
                'attr' => [
                    'class' => 'btn-success',
                ],
            ]);

        $builder->get('product')->addModelTransformer(new CallbackTransformer(
            function ($product) {
                if ($product && $product->getId()) {
                    $productEntity = $this->_getProductRepository()->find($product->getId());

                    return ['id' => $productEntity->getId(), 'text' => $productEntity->getName(), 'details' => $productEntity->getDetails()];
                }

                return null;
            },
            function ($product) {
                $productEntity = null;
                if ($product) {
                    $productEntity = $this->_getProductRepository()->find($product);
                }

                return $productEntity;
            }
        ));

        $builder->get('customer')->addViewTransformer(new CallbackTransformer(
            function ($data) {
                if ($data instanceof \DbBundle\Entity\Customer) {
                    return $data->getId();
                }
                if (!is_null($data)) {
                    if ($data) {
                        $data = $this->getCustomerRepository()->findById($data, \Doctrine\ORM\Query::HYDRATE_ARRAY);

                        return $data['id'];
                    }

                    return null;
                }

                return $data;
            },
            function ($data) {
                return $data;
            }
        ));

        $builder->get('customer')->addModelTransformer(new CallbackTransformer(
            function ($data) {
                if ($data instanceof \DbBundle\Entity\Customer
                    && method_exists($data, '__isInitialized')
                    && $data->__isInitialized() === false
                ) {
                    $data->__load();
                }

                return $data;
            },
            function ($data) {
                if ($data && !($data instanceof \DbBundle\Entity\Customer)) {
                    return $this->getCustomerRepository()->find($data);
                }

                return null;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\CustomerProduct',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'customerProduct',
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                $validationGroups = ['default'];

                if ($data->hasBalance()) {
                    $validationGroups[] = 'withBalance';
                }

                return $validationGroups;
            },
            'customerId' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'CustomerProduct';
    }

    /**
     * @return \DbBundle\Repository\ProductRepository
     */
    private function _getProductRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Product');
    }

    /**
     * @return \DbBundle\Repository\CustomerRepository
     */
    private function getCustomerRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Customer');
    }
}
