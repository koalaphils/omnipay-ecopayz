<?php

namespace ApiBundle\Form\MemberProduct;

use DbBundle\Repository\ProductRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ApiBundle\Request\CreateMemberProductRequest\MemberProduct;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

class MemberProductType extends AbstractType
{
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('product', Type\TextType::class, [
            'model_transformer' => $this->fieldCallbackTransformer(),
        ]);
        $builder->add('username', Type\TextType::class, [
            'model_transformer' => $this->fieldCallbackTransformer(),
        ]);
        $builder->add('isAgree', Type\CheckboxType::class, [
            'model_transformer' => $this->fieldCallbackTransformer(),
        ]);

        $this->onPostSubmit($builder);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MemberProduct::class,
            'csrf_protection' => false,
        ]);
    }

    private function onPostSubmit(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT,
            function(FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if ($data instanceof MemberProduct) {
                    $product = $this->getProductRepository()->findOneByCode($data->getProduct());

                    if ($product->hasUsername() && empty($data->getUsername())) {
                        $form->get('username')->addError(new FormError('Username is required.'));
                    }

                    if ($product->hasTerms() && $data->getIsAgree() == false) {
                        $form->get('isAgree')->addError(new FormError('You must agree to the terms & conditions.'));
                    }
                }
            }
        );
    }

    private function fieldCallbackTransformer(): CallbackTransformer
    {
        return new CallbackTransformer(
            function ($data) {
                return is_null($data) ? '' : $data;
            },
            function ($data) {
                return is_null($data) ? '' : $data;
            }
        );
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->productRepository;
    }
}