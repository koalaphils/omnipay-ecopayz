<?php

namespace NoticeBundle\Form;

use AppBundle\Form\BaseType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;
use DbBundle\Entity\Notice;

class NoticeType extends BaseType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', Type\TextType::class, [
            'label' => 'fields.title',
            'required' => true,
            'translation_domain' => 'NoticeBundle',
        ])->add('description', CType\MarkdownType::class, [
            'label' => 'fields.description',
            'translation_domain' => 'NoticeBundle',
        ])->add('type', Type\ChoiceType::class, [
            'choices' => [
                'noticeType.general' => Notice::NOTICE_TYPE_GENERAL,
                'noticeType.deposit' => Notice::NOTICE_TYPE_DEPOSIT,
                'noticeType.withdraw' => Notice::NOTICE_TYPE_WITHDRAW,
            ],
            'label' => 'fields.type',
            'choices_as_values' => true,
            'required' => true,
            'translation_domain' => 'NoticeBundle',
        ])->add('startAt', Type\DateTimeType::class, [
            'label' => 'fields.startAt',
            'format' => 'MM/dd/yyyy h:mm:ss a',
            'widget' => 'single_text',
            'required' => true,
            'translation_domain' => 'NoticeBundle',
        ])->add('endAt', Type\DateTimeType::class, [
            'label' => 'fields.endAt',
            'format' => 'MM/dd/yyyy h:mm:ss a',
            'widget' => 'single_text',
            'required' => true,
            'translation_domain' => 'NoticeBundle',
        ])->add('isActive', CType\SwitchType::class, [
            'label' => 'fields.isActive',
            'required' => false,
            'translation_domain' => 'NoticeBundle',
        ])->add('image', CType\MediaType::class, [
            'label' => 'fields.image',
            'translation_domain' => 'BonusBundle',
            'required' => false,
        ]);

        if ($options['mapped']) {
            $builder->add('btnGroup', CType\GroupType::class, [
                'attr' => [
                    'class' => 'pull-right',
                ],
            ]);
            $builder->get('btnGroup')->add('save', Type\SubmitType::class, [
                'label' => 'form.save',
                'translation_domain' => 'AppBundle',
            ]);
            if ($builder->getData() && $builder->getDataClass() == Notice::class) {
                $builder->get('btnGroup')->add('delete', Type\ButtonType::class, [
                    'label' => 'form.delete',
                    'translation_domain' => 'AppBundle',
                    'attr' => [
                        'class' => 'btn-danger waves-effect waves-light',
                        'data-toggle' => 'modal',
                        'data-target' => '#delete-modal',
                    ],
                ]);
            }
        }
    }

    public function finishView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        $view->children['startAt']->vars['half'] = true;
        $view->children['endAt']->vars['half'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\Notice',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'notice',
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Notice';
    }
}
