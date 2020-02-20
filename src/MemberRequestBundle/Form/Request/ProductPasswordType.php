<?php

namespace MemberRequestBundle\Form\Request;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;
use AppBundle\Form\Type as CType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use MemberRequestBundle\Model\MemberRequest\ProductPassword as ProductPasswordModel;
use Symfony\Component\Form\CallbackTransformer;

class ProductPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('password', Type\TextType::class, [
            'translation_domain' => 'MemberRequestBundle',
            'label' => 'product_password_fields.password',
            'required' => false,
        ])->add('member_product_id', Type\HiddenType::class, [
            'label' => 'Product Id',
            'translation_domain' => false,
            'required' => false,
        ]);
        
        $builder->get('password')
            ->addModelTransformer(new CallbackTransformer(
                function ($data) {
                    // transform the array to a string

                    return is_null($data) ? '' : $data;
                },
                function ($data) {
                    // transform the string back to an array
                    return is_null($data) ? '' : $data;
                }
        ));
    }

    public function finishView(FormView $formView, FormInterface $formInterface, array $options)
    {
        parent::finishView($formView, $formInterface, $options);

        foreach ($formInterface->getConfig()->getOption('formElementsViewOnly') as $field => $isView) {
            if (!is_array($isView)) {
                $formView->children[$field]->vars['view'] = $isView;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'constraints' => [new Valid()],
            'formElementsViewOnly' => [],
            'formElementsToUnmap' => [],
            'requestStarted' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'productPassword';
    }
}