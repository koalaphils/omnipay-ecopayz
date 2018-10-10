<?php

namespace CustomerBundle\Widget;

use AppBundle\Exceptions\FormValidationException;
use AppBundle\Widget\AbstractWidget;
use AppBundle\Form\Type as CType;
use DbBundle\Entity\AffiliateCommission;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Product;
use DbBundle\Repository\AffiliateCommissionRepository;
use DbBundle\Repository\ProductRepository;
use DbBundle\Repository\CustomerRepository;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\CallbackTransformer;

class AffiliateCommissionForm extends AbstractWidget
{
    protected function getBlockName(): string
    {
        return 'affiliateCommissionForm';
    }

    public static function defineDetails(): array
    {
        return [
            'affiliate' => [
                'type' => Type\NumberType::class,
                'options' => [],
            ],
            'commission' => [
                'type' => Type\TextType::class,
                'options' => [],
            ],
        ];
    }

    public function onAddCommission(array $data = []): JsonResponse
    {
        $request = new Request([], $data);
        $request->setMethod('POST');
        $response = [];
        $statusCode = JsonResponse::HTTP_OK;

        $form = $this->getAddForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $statusCode = JsonResponse::HTTP_CREATED;
            $commission = $form->getData();

            $this->getEntityManager()->persist($commission);
            $this->getEntityManager()->flush($commission);

            $response['data'] = $commission;
        } else {
            $formValidation = new FormValidationException($form);
            $statusCode = JsonResponse::HTTP_UNPROCESSABLE_ENTITY;
            $response['errors'] = $formValidation->getErrors();
        }

        return $this->jsonResponse($response, $statusCode);
    }

    public function onUpdateCommission(array $data = []): JsonResponse
    {
        $request = new Request([], $data);
        $request->setMethod('POST');
        $response = [];
        $statusCode = JsonResponse::HTTP_OK;

        $form = $this->getUpdateForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $statusCode = JsonResponse::HTTP_CREATED;
            $commission = $form->getData();

            $this->getEntityManager()->persist($commission);
            $this->getEntityManager()->flush($commission);

            $response['data'] = $commission;
        } else {
            $formValidation = new FormValidationException($form);
            $statusCode = JsonResponse::HTTP_UNPROCESSABLE_ENTITY;
            $response['errors'] = $formValidation->getErrors();
        }

        return $this->jsonResponse($response, $statusCode);
    }

    public function getAffiliateCommission(): ?AffiliateCommission
    {
        $commissionId = $this->property('commission');
        if ($commissionId === null) {
            return null;
        }
        $commission = $this->getAffiliateCommissionRepository()->find($commissionId);

        return $commission;
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

    public function getAddForm(): \Symfony\Component\Form\Form
    {
        $affiliate = $this->getAffiliate();
        $affiliateCommission = new AffiliateCommission();
        $affiliateCommission->setAffiliate($affiliate);
        $affiliateCommission->setCurrency($affiliate->getCurrency());

        $formBuilder = $this->buildWidgetForm($affiliateCommission);
        $formBuilder->add('product', Type\NumberType::class);
        $formBuilder->add('commission', Type\NumberType::class);
        $formBuilder->add('status', CType\SwitchType::class);

        $formBuilder->get('product')->addModelTransformer(new CallbackTransformer(
            function ($data) {
                if ($data instanceof Product) {
                    return ['id' => $data->getId(), 'code' => $data->getCode(), 'name' => $data->getName()];
                }

                return $data;
            },
            function ($data) {
                if (!($data instanceof Product)) {
                    return $this->getProductRepository()->find($data);
                }

                return $data;
            }
        ));

        return $formBuilder->getForm();
    }

    public function getUpdateForm(): \Symfony\Component\Form\Form
    {
        $affiliateCommission = $this->getAffiliateCommission();

        $formBuilder = $this->buildWidgetForm($affiliateCommission);
        $formBuilder->add('commission', Type\NumberType::class);
        $formBuilder->add('status', CType\SwitchType::class);

        return $formBuilder->getForm();
    }

    public function onAvailableProducts(): JsonResponse
    {
        $affiliate = $this->getAffiliate();
        $commissions = $this->getAffiliateCommissionRepository()->findBy(['affiliate' => $affiliate->getId()]);
        $productIds = array_map(function ($commission) {
            return $commission->getProduct()->getId();
        }, $commissions);

        $availableProducts = $this->getProductRepository()->getProductNotInIds($productIds);

        return $this->jsonResponse(['results' => $availableProducts]);
    }

    public function isCreate(): bool
    {
        return $this->property('commission') === null;
    }

    protected function onRender(string $blockSuffix, array &$options): void
    {
        if (!array_has($options, 'form')) {
            $commission = $this->getAffiliateCommission();
            $options['commission'] = $commission;
            if ($commission === null) {
                $options['form'] = $this->getAddForm()->createView();
            } else {
                $options['form'] = $this->getUpdateForm()->createView();
            }
        }
    }

    protected function getView(): string
    {
        return 'CustomerBundle:Affiliate:Widget/commissionForm.html.twig';
    }

    private function jsonResponse($data, int $status = 200, array $headers = [], ?SerializationContext $context = null)
    {
        if ($this->container->has('jms_serializer')) {
            $json = $this->container->get('jms_serializer')->serialize($data, 'json', $context);

            return new JsonResponse($json, $status, $headers, true);
        }

        return new JsonResponse($data, $status, $headers);
    }

    private function buildWidgetForm(AffiliateCommission $data): FormBuilder
    {
        $formBuilder = $this->getFormFactory()->createNamedBuilder(
            $this->getFullId() . '_form',
            FormType::class,
            $data,
            [
                'csrf_protection' => false,
                'data_class' => AffiliateCommission::class,
                'validation_groups' => 'Default',
            ]
        );

        return $formBuilder;
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->container->get('doctrine')->getRepository(Product::class);
    }

    private function getAffiliateCommissionRepository(): AffiliateCommissionRepository
    {
        return $this->container->get('doctrine')->getRepository(AffiliateCommission::class);
    }

    private function getCustomerRepository(): CustomerRepository
    {
        return $this->container->get('doctrine')->getRepository(Customer::class);
    }

    private function getFormFactory(): FormFactory
    {
        return $this->container->get('form.factory');
    }

    private function getEntityManager(string $name = 'default'): \Doctrine\ORM\EntityManager
    {
        return $this->container->get('doctrine')->getManager($name);
    }
}
