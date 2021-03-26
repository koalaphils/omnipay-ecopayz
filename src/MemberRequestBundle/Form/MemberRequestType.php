<?php

namespace MemberRequestBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Validator\Constraints\Valid;
use DbBundle\Entity\MemberRequest;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormView;

class MemberRequestType extends AbstractType
{
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('number', Type\TextType::class, [
            'label' => 'fields.number',
            'translation_domain' => 'MemberRequestBundle',
            'required' => false,
            'mapped' => array_get($builder->getOption('formElementsToUnmap'), 'number', true),
            'attr' => [
                'readonly' => true,
            ],
            'required' => false,
        ])->add('date', Type\DateTimeType::class, [
            'label' => 'fields.date',
            'format' => 'MM/dd/yyyy h:mm:ss a',
            'widget' => 'single_text',
            'required' => true,
            'translation_domain' => 'MemberRequestBundle',
            'mapped' => array_get($builder->getOption('formElementsToUnmap'), 'date', true),
        ])->add('notes', Type\TextareaType::class, [
            'translation_domain' => 'MemberRequestBundle',
            'label' => 'fields.notes',
            'property_path' => 'details[notes]',
            'required' => false,
        ]);

        $this->showByRequest($builder, $options);

        $builder->add('actions', CType\GroupType::class, [
            'attr' => [
                'class' => 'pull-right',
            ],
        ]);

        foreach ($builder->getOption('actions') as $key => $action) {
            $builder->get('actions')->add('btn_' . strtolower($action['label']), Type\SubmitType::class, [
                'label' => $action['label'],
                'translation_domain' => false,
                'attr' => [
                    'value' => "$key",
                    'class' => array_get($action, 'class', ''),
                ],
            ]);
        }

        $this->showGenerateButton($builder, $options);
    }

    public function finishView(FormView $formView, FormInterface $formInterface, array $options)
    {
        parent::finishView($formView, $formInterface, $options);

        foreach ($formInterface->getConfig()->getOption('formElementsViewOnly') as $field => $isView) {
            if (!is_array($isView)) {
                $formView->children[$field]->vars['view'] = $isView;
            } else {
                $i = 0;
                foreach($isView as $key => $value) {
                    $formView->children[$field]->children[$i]->vars[$key] = $value;
                    $i++;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MemberRequest::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'memberRequest',
            'cascade_validation' => true,
            'constraints' => [new Valid()],
            'actions' => [],
            'formElementsViewOnly' => [],
            'formElementsToUnmap' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'MemberRequest';
    }

    private function showByRequest(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($options) {
            $memberRequest = $event->getData();
            $form = $event->getForm();
            $class = '';
            $requests = '';
            if ($memberRequest->isKyc()) {
                $class = Request\KycType::class;
            } elseif ($memberRequest->isProductPassword()) {
                $class = Request\ProductPasswordType::class;
            }

            if (!$memberRequest->isGoogleAuth()) {
                $form->add('subRequests', Type\CollectionType::class, [
                    'entry_type' => $class,
                    'constraints' => [new Valid()],
                    'allow_add' => true,
                    'entry_options' => [
                        'formElementsViewOnly' => $options['formElementsViewOnly']['subRequests'] ?? [],
                        'formElementsToUnmap' => $options['formElementsToUnmap']['subRequests'] ?? [],
                        'requestStarted' => $memberRequest->isStart(),
                    ],
                ]);
            }
        });
    }

    private function showGenerateButton(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $memberRequest = $event->getData();
            $form = $event->getForm();
            if ($memberRequest->isProductPasswordHadBeenAcknowledged()) {
                $form->add('generatePassword', Type\ButtonType::class, [
                    'label' => 'Generate Password',
                    'translation_domain' => false,
                    'attr' => [
                        'class' => 'btn-warning pull-right',
                    ],
                ]);
            }
        });
    }

}
