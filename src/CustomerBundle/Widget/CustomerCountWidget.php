<?php

namespace CustomerBundle\Widget;

use AppBundle\Widget\AbstractWidget;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\JsonResponse;

class CustomerCountWidget extends AbstractWidget
{
    public function count()
    {
        return $this->getCustomerRepository()->getCustomerListFilterCount([
            'isCustomer' => true,
        ]);
    }

    public static function defineDetails(): array
    {
        return [
            'title' => 'Member List'
        ];
    }

    public static function defineProperties($container): array
    {
        return [
            'status' => [
                'type' => Type\ChoiceType::class,
                'options' => [
                    'choices' => [
                        'Registered' => 'registered',
                        'Enabled' => 'enabled',
                        'Suspended' => 'suspended',
                    ],
                    'multiple' => true,
                    'attr' => array(
                        'class' =>'form-control selectpicker',
                        'title' =>'Select Status',
                    )
                ],
            ],
            'limit' => [
                'type' => Type\ChoiceType::class,
                'options' => [
                    'choices' => [
                        1 => 1,
                        2 => 2,
                        3 => 3,
                        4 => 4,
                        5 => 5       
                    ],
                    'attr' => array(
                        'class' =>'form-control selectpicker customerLimit'
                    )
                ],
            ],
        ];
    }

    public function onGetCustomer($data)
    {
        $limit = $this->property('limit', 0);
        $page = $this->property('page', 1);
        $offset = ($page - 1) * $limit;
        $status = $this->property('status');

        if ($limit === 0) {
            $limit = null;
        }

        $customer = $this->getCustomerRepository()->getCustomer([
            'status' => $status,
            [['column' => 'c.joinedAt', 'dir' => 'desc']],
            'limit' => $limit,
            'offset' => $offset,
        ]);

        dump($customer);

        if ($this->container->has('jms_serializer')) {
            $context = new \JMS\Serializer\SerializationContext();
            $context->setSerializeNull(true);
            $context->setGroups([
                'Search',
                '_link',
                'Default',
                'customer',
                'customer' => ['name', 'user'],
                'createdBy',
                'details',
            ]);

            $json = $this->container->get('jms_serializer')->serialize($customer, 'json', $context);

            return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
        }

        return $customer;
    }

    protected function getView()
    {
        return 'CustomerBundle:Widget:widget.html.twig';
    }

    protected function getBlockName(): string
    {
        return 'customerCounter';
    }


    private function getCustomerRepository(): \DbBundle\Repository\CustomerRepository
    {
        return $this->getEntityManager()->getRepository(\DbBundle\Entity\Customer::class);
    }

    private function getEntityManager(string $name = 'default'): \Doctrine\ORM\EntityManagerInterface
    {
        return $this->getDoctrine()->getManager($name);
    }

    private function getDoctrine(): \Symfony\Bridge\Doctrine\RegistryInterface
    {
        return $this->container->get('doctrine');
    }
}
