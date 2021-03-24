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

class KycType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('filename', Type\HiddenType::class, [
            'translation_domain' => 'MemberRequestBundle',
            'label' => 'kyc_fields.filename',
            'required' => false,
        ]);
        if (!$options['requestStarted']) {
            $builder->add('remark', Type\TextType::class, [
                'translation_domain' => 'MemberRequestBundle',
                'label' => 'kyc_fields.remark',
                'required' => false,
            ]);
        }
    }

    public function finishView(FormView $formView, FormInterface $formInterface, array $options)
    {
        parent::finishView($formView, $formInterface, $options);

        foreach ($formInterface->getConfig()->getOption('formElementsViewOnly') as $field => $isView) {
            if (!is_array($isView) and !$options['requestStarted']) {
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
        return 'kyc';
    }
}