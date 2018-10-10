<?php

namespace CustomerBundle\Widget;

use AppBundle\Widget\AbstractWidget;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\Response;
use DbBundle\Repository\AffiliateCommissionRepository;
use DbBundle\Entity\AffiliateCommission;
use DbBundle\Repository\CustomerRepository;
use DbBundle\Entity\Customer;
use DbBundle\Repository\CustomerProductRepository;
use DbBundle\Entity\CustomerProduct;

class AffiliateCommissionListWidget extends AbstractWidget
{
    protected function getBlockName(): string
    {
        return 'affiliateCommissionList';
    }

    public static function defineDetails(): array
    {
        return [
            'title' => 'Affiliate CommissionList'
        ];
    }

    public static function defineProperties($container): array
    {
        return [
            'affiliate' => [
                'type' => Type\NumberType::class,
                'options' => [],
            ],
            'canUpdate' => [
                'type' => Type\NumberType::class,
            ],
            'canAdd' => [
                'type' => Type\NumberType::class,
            ]
        ];
    }

    public function canAdd(): bool
    {
        return $this->property('canAdd') == 1;
    }

    public function canUpdate(): bool
    {
        return $this->property('canUpdate') == 1;
    }

    public function onGetCommissions(array $data = []): array
    {
        $response = [
            'records' => [],
            'recordsFiltered' => 2,
            'recordsTotal' => 2,
            'limit' => $data['limit'] ?? 10,
            'page' => $data['page'] ?? 1,
        ];
        $affiliate = $this->getAffiliate();
        $response['records'] = $this->getAffiliateCommissionRepository()->findByAffiliateId($this->property('affiliate'));
        $productIds = array_map(function ($commission) {
            return $commission->getProduct()->getId();
        }, $response['records']);
        $numOfCustomers = $this->getCustomerProductRepository()->getTotalCustomerProductByAffiliate($affiliate, $productIds);
        $modifiedKeyNumOfCustomers = [];
        foreach ($numOfCustomers as $count) {
            $modifiedKeyNumOfCustomers[$count['productId'] . '_' . $count['currencyId']] = $count;
        }
        $response['counts'] = $modifiedKeyNumOfCustomers;

        return $response;
    }

    public function onRenderFormView(array $data = []): Response
    {
        $formWidget = $this->getChild('commission_form');
        if (isset($data['commission'])) {
            $properties = $formWidget->getProperties();
            $properties['commission'] = $data['commission'] ?? null;
            $formWidget->setProperties($properties);
            $formWidget->init();
            $formWidget->run();
        }

        return new Response($formWidget->renderBlock('form'));
    }

    public function onSuspendCommission(array $data): array
    {
        $commissionId = $data['commission'];
        $commission = $this->getAffiliateCommissionRepository()->find($commissionId);
        $commission->suspend();

        $this->getEntityManager()->persist($commission);
        $this->getEntityManager()->flush($commission);

        return ['data' => $commission];
    }

    public function onActivateCommission(array $data): array
    {
        $commissionId = $data['commission'];
        $commission = $this->getAffiliateCommissionRepository()->find($commissionId);
        $commission->activate();

        $this->getEntityManager()->persist($commission);
        $this->getEntityManager()->flush($commission);

        return ['data' => $commission];
    }

    public function getAffiliate(): ?Customer
    {
        $affiliateId = $this->property('affiliate');
        $affiliate = $this->getCustomerRepository()->find($affiliateId);

        if ($affiliate->getIsAffiliate()) {
            return $affiliate;
        }

        return null;
    }

    protected function getView(): string
    {
        return 'CustomerBundle:Affiliate:Widget/commissionList.html.twig';
    }

    protected function onInit()
    {
        $properties = $this->getProperties();

        if ($this->canAdd() || $this->canUpdate()) {
            $properties['children'] = [
                'commission_form' => [
                    'type' => 'affiliateCommissionForm',
                    'definition' => [
                        'id' => 'commission_form',
                    ],
                    'properties' => [
                        'affiliate' => $properties['affiliate'],
                    ]
                ],
            ];
        }

        $this->setProperties($properties);
    }

    private function getAffiliateCommissionRepository(): AffiliateCommissionRepository
    {
        return $this->container->get('doctrine')->getRepository(AffiliateCommission::class);
    }

    private function getCustomerRepository(): CustomerRepository
    {
        return $this->container->get('doctrine')->getRepository(Customer::class);
    }

    private function getCustomerProductRepository(): CustomerProductRepository
    {
        return $this->container->get('doctrine')->getRepository(CustomerProduct::class);
    }

    private function getEntityManager(string $name = 'default'): \Doctrine\ORM\EntityManager
    {
        return $this->container->get('doctrine')->getManager($name);
    }
}
