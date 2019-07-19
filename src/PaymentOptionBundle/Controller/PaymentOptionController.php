<?php

namespace PaymentOptionBundle\Controller;

use AppBundle\Controller\AbstractController;
use AppBundle\Exceptions\FormValidationException;
use DbBundle\Entity\Transaction;
use JMS\Serializer\EventDispatcher\EventDispatcherInterface;
use PaymentOptionBundle\Event\PaymentOptionExtendEvent;
use PaymentOptionBundle\Form\Configuration\ConfigurationType;
use PaymentOptionBundle\Form\PaymentOptionType;
use PaymentBundle\Form\BitcoinRateSettingsDTOType;
use PaymentBundle\Form\BitcoinWithdrawalRateSettingsDTOType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PaymentBundle\Manager\BitcoinManager;
use PaymentBundle\Form\BitcoinSettingType;
use PaymentBundle\Model\Bitcoin\SettingModel;
use PaymentBundle\Component\Blockchain\Rate;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

class PaymentOptionController extends AbstractController
{
    public function listPageAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_VIEW']);

        return $this->render('PaymentOptionBundle:PaymentOption:list.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_VIEW']);

        $filters = $request->request->all();
        $filters = array_merge($filters, $request->query->all());
        $data = $this->getManager()->filter($filters);

        return $this->response($request, $data, ['groups' => ['Default', '_link']]);
    }

    public function createPageAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_CREATE']);

        $form = $this->createForm(PaymentOptionType::class, null, [
            'action' => $this->generateUrl('paymentoption.save', ['id' => 'new']),
            'method' => 'POST',
        ]);

        return $this->render('PaymentOptionBundle:PaymentOption:create.html.twig', ['form' => $form->createView()]);
    }

    public function updatePageAction(Request $request, $id, $activeTab): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_UPDATE']);
        /* @var $paymentOption \DbBundle\Entity\PaymentOption */
        $paymentOption = $this->getPaymentOptionRepository()->find($id);
        if ($paymentOption === null) {
            throw $this->createNotFoundException();
        }
        $paymentOption->sortFieldsAscending();

        $form = $this->createForm(PaymentOptionType::class, $paymentOption, [
            'action' => $this->generateUrl('paymentoption.save', ['id' => $id]),
            'method' => 'POST',
            'id' => $id,
        ]);

        $configurationForm = $this->createForm(ConfigurationType::class, $paymentOption->getConfigurations(), [
            'action' => $this->generateUrl('paymentoption.configuration_save', ['code' => $paymentOption->getCode()])
        ]);
        $viewParameters = ['form' => $form->createView(), 'configurationForm' => $configurationForm->createView()];

        $extendEvent = new PaymentOptionExtendEvent($paymentOption, 'PaymentOptionBundle:PaymentOption:update.html.twig', $viewParameters);

        $this->getEventDispatcher()->dispatch('paymentoption.updatepage.extend', $extendEvent);

        if ($this->getTwig()->getLoader()->exists($extendEvent->getView())) {
            return $this->render($extendEvent->getView(), $extendEvent->getParameters());
        }

        return $this->render('PaymentOptionBundle:PaymentOption:update.html.twig', $extendEvent->getParameters());
    }

    public function saveConfigurationAction(Request $request, string $code): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_UPDATE']);
        /* @var $paymentOption \DbBundle\Entity\PaymentOption */
        $paymentOption = $this->getPaymentOptionRepository()->find($code);
        if ($paymentOption === null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ConfigurationType::class, $paymentOption->getConfigurations());

        try {
            $this->getManager()->handleConfigurationForm($paymentOption, $form, $request);
            $response['success'] = true;
            $response['data'] = $paymentOption;
        } catch (FormValidationException $e) {
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']]);
    }

    public function saveAction(Request $request, $id)
    {
        $form = $this->createForm(PaymentOptionType::class);
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_CREATE']);
            $paymentOption = new \DbBundle\Entity\PaymentOption();
        } else {
            $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_UPDATE']);
            $paymentOption = $this->getPaymentOptionRepository()->find($id);
            $form->remove('code');
        }
        $form->setData($paymentOption);
        $response = ['success' => false];

        try {
            $paymentOption = $this->getManager()->handleCreateForm($form, $request);
            $response['success'] = true;
            $response['data'] = $paymentOption;
        } catch (FormValidationException $e) {
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']]);
    }

    public function suspendAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_UPDATE']);
        $code = $request->request->get('code');
        $paymentOption = $this->getPaymentOptionRepository()->find($code);
        if (!$paymentOption) {
            throw new NoResultException();
        } else if ($paymentOption->getIsActive()) {
            $paymentOption->suspend();
            $this->getPaymentOptionRepository()->save($paymentOption);
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.suspended.title', [], 'PaymentOptionBundle'),
                'message'   => $this->getTranslator()->trans('notification.suspended.message', ['%name%' => $paymentOption->getName() . "(" . $paymentOption->getCode() . ")", ], 'PaymentOptionBundle'),
            ];
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);
                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK]);
            }
        } else {
            throw new \Exception($paymentOption->getName() . ' is already suspended', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function activateAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_UPDATE']);
        $code = $request->request->get('code');
        $paymentOption = $this->getPaymentOptionRepository()->find($code);
        if (!$paymentOption) {
            throw new NoResultException();
        } else if (!$paymentOption->getIsActive()) {
            $paymentOption->enable();
            $this->getPaymentOptionRepository()->save($paymentOption);
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.activated.title', [], 'PaymentOptionBundle'),
                'message'   => $this->getTranslator()->trans('notification.activated.message', ['%name%' => $paymentOption->getName() . "(" . $paymentOption->getCode() . ")", ], 'PaymentOptionBundle'),
            ];
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);
                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK]);
            }
        } else {
            throw new \Exception($paymentOption->getName() . ' is already activated', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    protected function getManager(): \PaymentOptionBundle\Manager\PaymentOptionManager
    {
        return $this->get('paymentoption.manager');
    }

    protected function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->getRepository(\DbBundle\Entity\PaymentOption::class);
    }
    
    protected function getBitcoinManager(): BitcoinManager
    {
        return $this->get('payment.bitcoin_manager');
    }

    protected function getTwig(): Environment
    {
        return $this->get('twig');
    }

    protected function getEventDispatcher(): \Symfony\Component\EventDispatcher\EventDispatcherInterface
    {
        return $this->get('event_dispatcher');
    }
}
