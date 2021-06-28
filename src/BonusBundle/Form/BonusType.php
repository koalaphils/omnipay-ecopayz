<?php

namespace BonusBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;
use DbBundle\Entity\Bonus;

class BonusType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('subject', Type\TextType::class, [
            'label' => 'fields.subject',
            'required' => true,
            'translation_domain' => 'BonusBundle',
        ])->add('startAt', Type\DateTimeType::class, [
            'label' => 'fields.startAt',
            'format' => 'MM/dd/yyyy h:mm:ss a',
            'widget' => 'single_text',
            'required' => true,
            'translation_domain' => 'BonusBundle',
        ])->add('endAt', Type\DateTimeType::class, [
            'label' => 'fields.endAt',
            'format' => 'MM/dd/yyyy h:mm:ss a',
            'widget' => 'single_text',
            'required' => true,
            'translation_domain' => 'BonusBundle',
        ])->add('isActive', CType\SwitchType::class, [
            'label' => 'fields.isActive',
            'required' => false,
            'translation_domain' => 'BonusBundle',
        ])->add('terms', CType\MarkdownType::class, [
            'label' => 'fields.terms',
            'translation_domain' => 'BonusBundle',
            'required' => false,
        ])->add('image', CType\MediaType::class, [
            'label' => 'fields.image',
            'translation_domain' => 'BonusBundle',
            'required' => false,
            'multiple' => false,
        ])->add('save', Type\SubmitType::class, [
            'label' => 'form.save',
            'translation_domain' => 'AppBundle',
            'attr' => [
                'class' => 'btn-success',
            ],
        ]);

        if ($builder->getData()->getId()) {
            $builder->add('delete', Type\ButtonType::class, [
                'label' => 'form.delete',
                'translation_domain' => 'AppBundle',
                'attr' => [
                    'class' => 'btn-danger',
                ],
            ]);
        }

        $builder->get('image')->addModelTransformer(new CallbackTransformer(
            function ($image) {
                if (is_array($image)) {
                    $image = empty($image) ? '' : $image[0];
                }

                return $image;
            },
            function ($image) {
                return $image;
            }
        ));
    }

    public function finishView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        $view->children['startAt']->vars['half'] = true;
        $view->children['startAt']->vars['widget_option'] = $view->children['endAt']->vars['widget_option'] = ['position' => ['vertical' => 'bottom']];
        $view->children['endAt']->vars['half'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Bonus::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'bonus',
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Gateway';
    }
}
