<?php

namespace ProductBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;
use DbBundle\Entity\Product;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('code', Type\TextType::class, [
            'label' => 'fields.code',
            'required' => true,
            'translation_domain' => 'ProductBundle',
        ])
        ->add('name', Type\TextType::class, [
            'label' => 'fields.name',
            'required' => true,
            'translation_domain' => 'ProductBundle',
        ])
        ->add('url', Type\UrlType::class, [
            'label' => 'fields.url',
            'required' => false,
            'translation_domain' => 'ProductBundle',
        ])
        ->add('isActive', CType\SwitchType::class, [
            'label' => 'fields.isActive',
            'required' => false,
            'translation_domain' => 'ProductBundle',
            'empty_data' => null,
        ])
        ->add('commission', Type\TextType::class, [
            'label' => 'fields.commission',
            'required' => true,
            'translation_domain' => 'ProductBundle'
        ])
        ->add('logo', CType\MediaType::class, [
            'label' => 'fields.logo',
            'translation_domain' => 'ProductBundle',
            'required' => false,
        ])
        ->add('save', Type\ButtonType::class, [
            'label' => 'form.save',
            'translation_domain' => 'AppBundle',
        ]);

        //this will hide temporary logo
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $form->remove('logo');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'ProductBundle\Request\ProductFormRequest',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'product',
            'validation_groups' => 'default',
            'cascade_validation' => true,
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Product';
    }
}
