<?php

namespace DWLBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\CallbackTransformer;

class DWLItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', Type\HiddenType::class);
        $builder->add('turnover', Type\TextType::class, []);
        $builder->add('gross', Type\TextType::class, []);
        $builder->add('winLoss', Type\TextType::class, []);
        $builder->add('commission', Type\TextType::class, []);
        $builder->add('amount', Type\TextType::class, []);
        $builder->add('_v', Type\HiddenType::class, ['property_path' => 'updatedAt']);
        $builder->add('save', Type\SubmitType::class, [
            'label' => 'form.save',
            'translation_domain' => 'AppBundle',
            'attr' => ['class' => 'btn-success'],
        ]);

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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => '\DWLBundle\Entity\DWLItem',
                'csrf_protection' => true,
                'csrf_field_name' => '_token',
                'csrf_token_id' => 'dwlItem',
                'validation_groups' => 'default',
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'DWLItem';
    }
}
