<?php

namespace TicketBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use AppBundle\Form\Type\Select2Type as SelectType2;
use AppBundle\Form\Type\TagType as TagType2;
use AppBundle\Form\Type\SummernoteType as SummernoteType2;
use DbBundle\Entity\Ticket;

class TicketType extends AbstractType
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    private $doctrine;

    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
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
            ->add('requester', SelectType2::class, [
                'label' => 'fields.requester',
                'translation_domain' => 'TicketBundle',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('user.list_search'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                    'data-id-column' => 'id',
                ],
                'required' => true,
            ])
            ->add('assignee', HiddenType::class, [
                'label' => 'fields.assignee',
                'required' => false,
                'translation_domain' => 'TicketBundle',
                'data' => $options['assignee'],
                //'mapped'                => true
            ])
            ->add('status', HiddenType::class, [
                'label' => 'fields.status',
                'required' => false,
                'translation_domain' => 'TicketBundle',
                'data' => Ticket::TICKET_STATUS_OPEN,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'type.question' => Ticket::TICKET_TYPE_QUESTION,
                    'type.incident' => Ticket::TICKET_TYPE_INCIDENT,
                    'type.problem' => Ticket::TICKET_TYPE_PROBLEM,
                    'type.task' => Ticket::TICKET_TYPE_TASK,
                ],
                'choices_as_values' => true,
                'label' => 'fields.type',
                'required' => true,
                'translation_domain' => 'TicketBundle',
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'priority.low' => Ticket::TICKET_PRIORITY_LOW,
                    'priority.normal' => Ticket::TICKET_PRIORITY_NORMAL,
                    'priority.high' => Ticket::TICKET_PRIORITY_HIGH,
                    'priority.urgent' => Ticket::TICKET_PRIORITY_URGENT,
                ],
                'choices_as_values' => true,
                'label' => 'fields.priority',
                'required' => true,
                'translation_domain' => 'TicketBundle',
            ])
            ->add('tag', TagType2::class, [
                'label' => 'fields.tag',
                'required' => false,
                'translation_domain' => 'TicketBundle',
            ])
            ->add('subject', TextType::class, [
                'label' => 'fields.subject',
                'required' => true,
                'translation_domain' => 'TicketBundle',
            ])
            ->add('description', SummernoteType2::class, [
                'label' => 'fields.description',
                'required' => true,
                'translation_domain' => 'TicketBundle',
            ])->add('save', SubmitType::class, [
                'label' => 'form.send',
                'translation_domain' => 'AppBundle',
                'attr' => [
                    'class' => !empty($options['assignee']) ? 'btn btn-primary waves-effect waves-light w-md m-b-30' : '',
                ],
            ]);

        $builder
            ->get('requester')
            ->addViewTransformer(new CallbackTransformer(
                function ($user) {
                    if ($user && !empty($options['assignee'])) {
                        $userEntity = $this->_getUserRepository()->getUserByZendeskId($user);

                        return [['id' => $userEntity->getZendeskId(), 'text' => $userEntity->getUsername()]];
                    }

                    return null;
                },
                function ($user) {
                    return $user;
                }
            ));

        if (!empty($options['assignee'])) {
            $builder
                ->remove('requester')
                ->remove('subject')
                ->remove('description')
                ->remove('assignee');

            $builder
                ->add('description', SummernoteType2::class, [
                    'label' => 'menus.Reply',
                    'required' => true,
                    'translation_domain' => 'AppBundle',
                    'data' => '',
                ])->add('status', ChoiceType::class, [
                    'choices' => [
                        'status.open' => Ticket::TICKET_STATUS_OPEN,
                        'status.pending' => Ticket::TICKET_STATUS_PENDING,
                        'status.hold' => Ticket::TICKET_STATUS_HOLD,
                        'status.solved' => Ticket::TICKET_STATUS_SOLVED,
                        'status.closed' => Ticket::TICKET_STATUS_CLOSED,
                    ],
                    'choices_as_values' => true,
                    'label' => 'fields.status',
                    'required' => true,
                    'translation_domain' => 'TicketBundle',
                ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'ticket',
            'validation_groups' => 'default',
            'assignee' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Ticket';
    }

    /**
     * @return \DbBundle\Repository\UserRepository
     */
    private function _getUserRepository()
    {
        return $this->doctrine->getRepository('DbBundle:User');
    }
}
