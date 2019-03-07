<?php

namespace TransactionBundle\Widget;

use AppBundle\Widget\AbstractWidget;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransactionListWidget extends AbstractWidget
{
    private $transactions;

    public static function defineProperties($container): array
    {
        return [
            'status' => [
                'type' => Type\ChoiceType::class,
                'options' => [
                    'choices' => static::getStatuses($container->get('app.setting_manager')),
                    'multiple' => true,
                    'attr' => array(
                        'class' =>'form-control selectpicker',
                        'title' =>'Select Status',
                        'id' => 'transactionStatus'
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
                        5 => 5 ,
                        'Show All' => 0           
                    ],
                    'attr' => array(
                        'class' =>'form-control selectpicker transactionLimit',
                        'title' =>'Limit'
                    )
                ],
            ],
        ];
    }

    public function onGetTransactions($data)
    {
        $limit = $this->property('limit', 0);
        $page = $this->property('page', 1);
        $offset = ($page - 1) * $limit;
        $status = $this->property('status');

        if ($limit === 0) {
            $limit = null;
        }
        
        // $transactions = $this->getTransactionRepository()->findTransactions(
        //     ['status' => $status],
        //     [['column' => 'transaction.date', 'dir' => 'desc']],
        //     $limit,
        //     $offset
        // );

        $transactions = $this->getTransactionRepository()->findTransactions(
            ['status' => $status],
            [],
            $limit,
            $offset
        );

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
            ]);

            $json = $this->container->get('jms_serializer')->serialize($transactions, 'json', $context);

            return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
        }

        return $transactions;
    }

    public static function defineDetails(): array
    {
        return [
            'title' => 'Transaction List',
        ];
    }

    protected static function getStatuses(\AppBundle\Manager\SettingManager $settingManager): array
    {
        $statusSettings = $settingManager->getSetting('transaction.status');
        $statuses = [];


        foreach ($statusSettings as $key => $status) {
            $statuses[$status['label']] = $key;
        }
        $statuses['Voided'] = 'voided';

        return $statuses;
    }

    protected function getBlockName(): string
    {
        return 'transactionList';
    }

    protected function getView()
    {
        return 'TransactionBundle:Widget:widget.html.twig';
    }

    private function getTransactionRepository(): \DbBundle\Repository\TransactionRepository
    {
        return $this->getEntityManager()->getRepository(\DbBundle\Entity\Transaction::class);
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
