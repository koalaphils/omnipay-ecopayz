<?php

namespace MemberBundle\Widget\Page;

use AppBundle\Widget\AbstractPageWidget;
use DbBundle\Entity\Customer as Member;
use Doctrine\ORM\PersistentCollection;
use DbBundle\Entity\PaymentOption;

class PaymentOptionWidget extends AbstractPageWidget
{
    private $member;

    public static function defineDetails(): array
    {
        return ['title' => 'Member Payment Option List'];
    }

    protected function getBlockName(): string
    {
        return 'memberPaymentOptionList';
    }

    public function onRun()
    {
        $member = $this->property('member');
        if ($member instanceof Member) {
            $this->member = $member;
        } elseif (is_array($member)) {
            $function = $member['method'];
            $this->member = $this->getResultFromFunction($function, $member['arguments']);
        }
    }

    public function onGetList(): string
    {
        $template = $this->getTemplate();
        $paymentOptions = $this->getMember()->getPaymentOptions();
        $this->getEntityManager()->initializeObject($paymentOptions);
        //Note: Will disable this for now since we don't have Ecopayz in PIWI.
        //$paymentOptions = $this->listOneActiveEcopayzPaymentOnly($paymentOptions);

        $list = $this->renderBlock('widget', ['list' => $paymentOptions]);

        return $list;
    }

    public function onSave(array $data): array
    {
        $hasDuplicateCodeValueInMemberPaymentOption = $this->getDuplicateFields($data);
        
        if (!empty($hasDuplicateCodeValueInMemberPaymentOption)) {
            return [
                'data' => [],
                'withErrors' => $hasDuplicateCodeValueInMemberPaymentOption,
                '__notifications' => $this->generateNotification('Validation Failed', 'Some fields are invalid', 'error'),
            ];
        } 
        
        if (isset($data['id'])) {
            $memberPaymentOption = $this->getMemberPaymentOptionRepository()->findByidAndCustomer($data['id'], $this->getMember()->getId());
            $request = \MemberBundle\Request\UpdatePaymentOptionRequest::fromEntity($memberPaymentOption);
            $handler = $this->getUpdatePaymentOptionHandler();
        } else {
            $request = \MemberBundle\Request\CreatePaymentOptionRequest::fromEntity($this->getMember());
            $handler = $this->getCreatePaymentOptionHandler();
        }

        $request->setFields($data['fields']);
        $request->setIsActive($data['isActive'] ?? false);
        $request->setType($data['paymentOption']);

        $response = [
            'data' => $handler->handle($request),
            '__notifications' => $this->generateNotification('Saved', 'Successfully Saved', 'success'),
        ];

        return $response;
    }

    public function onGetForm(array $data): string
    {
        $paymentOptions = $this->getPaymentOptionTypes();
        $type = $data['type'] ?? '';
        $paymentOptionId = trim($data['paymentOption'] ?? '');
        if ($paymentOptionId !== '') {
            $paymentOption = $this
                ->getMemberPaymentOptionRepository()
                ->findByidAndCustomer($paymentOptionId, $this->getMember()->getId())
            ;
        } else {
            $paymentOption = new \DbBundle\Entity\CustomerPaymentOption();
        }

        return $this->renderBlock(
            'form',
            ['paymentOptions' => $paymentOptions, 'type' => $type, 'paymentOption' => $paymentOption],
            $this->property('formTemplate')
        );
    }

    protected function getView(): string
    {
        return 'MemberBundle:Widget:Page/paymentoption-list.html.twig';
    }

    private function getDuplicateFields(array $data = []): array
    {
        $paymentOption = $this->getPaymentOptionRepository()->find($data['paymentOption']);
        $codes = $paymentOption->getCodeOfUniqueField();
        $duplicateResult = [];
        $memberPaymentOptionId = isset($data['id']) ? $data['id'] : null;
        foreach ($codes as $code) {
            $codeValue = $data['fields'][$code];
            if (!is_null($this->getMemberPaymentOptionRepository()->findMemberPaymentOptionDuplicateInCodeAndValue($memberPaymentOptionId, $data['paymentOption'], $code, $codeValue))) {
                $duplicateResult[] = [
                    $code => $codeValue,
                ];
            }
        }

        return $duplicateResult;
    }

    private function listOneActiveEcopayzPaymentOnly(PersistentCollection $customerPaymentOptions) : PersistentCollection
    {
        $customerPaymentOptionsToArray = $customerPaymentOptions;
        if (!empty($customerPaymentOptionsToArray->toArray())) {
            $newCustomerPaymentOptions = [];
            foreach ($customerPaymentOptionsToArray->toArray() as $key => $customerPaymentOption) {
                $hasInactiveEcopyz = false;
                $paymentOption = $customerPaymentOption->getPaymentOption();
                if ($paymentOption->getCode() === strtoupper(PaymentOption::PAYMENT_MODE_ECOPAYZ) && $customerPaymentOption->getIsActive() === false) {
                    $hasInactiveEcopyz = true;
                }

                if (!$hasInactiveEcopyz) {
                    $newCustomerPaymentOptions[] = $customerPaymentOption;
                }
            }

            return $this->bindNewCustomerPaymentOptions($customerPaymentOptions, $newCustomerPaymentOptions);
        }

        return $customerPaymentOptions;
    }

    private function bindNewCustomerPaymentOptions(PersistentCollection $customerPaymentOptions, array $paymentOptions = []): PersistentCollection
    {
        $customerPaymentOptions->clear();
        if (!empty($paymentOptions)) {
            foreach ($paymentOptions as $paymentOption) {
                $customerPaymentOptions->add($paymentOption);
            }
        }
        
        return $customerPaymentOptions;
    }

    private function getPaymentOptionTypes()
    {
        $paymentOptions = [
        ];

        foreach ($this->getPaymentOptionRepository()->filter() as $paymentOption) {
            if ($paymentOption->getIsActive()) {
                $paymentOptions[$paymentOption->getCode()] = $paymentOption;
            }
        }

        return $paymentOptions;
    }

    private function generateNotification(string $title, string $message, string $type = 'success'): array
    {
        return [
            [
                'title' => $title,
                'message' => $message,
                'type' => $type,
            ]
        ];
    }

    private function getMember(): Member
    {
        return $this->member;
    }

    private function getMemberPaymentOptionRepository(): \DbBundle\Repository\CustomerPaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository(\DbBundle\Entity\CustomerPaymentOption::class);
    }

    private function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository(\DbBundle\Entity\PaymentOption::class);
    }

    private function getEntityManager(): \Doctrine\ORM\EntityManager
    {
        return $this->getDoctrine()->getManager();
    }

    private function getDoctrine(): \Doctrine\Bundle\DoctrineBundle\Registry
    {
        return $this->container->get('doctrine');
    }

    private function getCreatePaymentOptionHandler(): \MemberBundle\RequestHandler\CreatePaymentOptionHandler
    {
        return $this->container->get('member.handler.create_payment_option');
    }

    private function getUpdatePaymentOptionHandler(): \MemberBundle\RequestHandler\UpdatePaymentOptionHandler
    {
        return $this->container->get('member.handler.update_payment_option');
    }
}
