<?php

namespace GatewayBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use GatewayBundle\Form\GatewayType;
use DbBundle\Entity\Gateway;
use DbBundle\Entity\PaymentOption;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_VIEW']);

        return $this->render('GatewayBundle:Default:index.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_VIEW']);
        $filters = $request->request->all();
        $filters = array_merge($filters, $request->query->all());
        $results = $this->getManager()->getGatewayList($filters);

        return new JsonResponse($results, JsonResponse::HTTP_OK);
    }

    public function findByLevelAction($level)
    {
        try {
            $result = $this->getGatewayRepository()->findByLevel($level, \Doctrine\ORM\Query::HYDRATE_ARRAY);

            return new JsonResponse($result, 200);
        } catch (\Doctrine\ORM\NoResultException $e) {
            return new JsonResponse($e->getMessage(), 404);
        } catch (\Doctrine\ORM\NonUniqueResultException $e) {
            return new JsonResponse($e->getMessage(), 409);
        } catch (\Exception $e) {
            $code = $e->getCode();
            if ($code === 0) {
                $code = 400;
            }

            return new JsonResponse($e->getMessage(), $code);
        }
    }

    public function viewAction(Request $request, $id)
    {
    }

    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_CREATE']);
        $form = $this->createForm(GatewayType::class, null, [
            'action' => $this->getRouter()->generate('gateway.save'),
        ]);
        $form->handleRequest($request);

        return $this->render('GatewayBundle:Default:create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_UPDATE']);
        $this->getMenuManager()->setActive('gateway.list');

        $gateway = $this->getGatewayRepository()->getWithCurrency($id);
        $paymentOptions = $this->getPaymentOptionRepository()->findAll();
        $paymentOptionModes = [];

        foreach ($paymentOptions as $paymentOption) {
            $paymentOptionModes[$paymentOption->getCode()] = $paymentOption->getPaymentMode();
        }

        $form = $this->createForm(GatewayType::class, $gateway, [
            'action' => $this->getRouter()->generate('gateway.save', ['id' => $id]),
        ]);

        $paymentForms = [];
        foreach ($this->getGatewayFormManager()->getModes() as $key => $configs) {
            $paymentForms[$key] = $this->getGatewayFormManager()->getForm($key, $gateway->getConfig(), [
                'block_prefix' => 'config',
            ])->createView($form->get('details')->createView());
        }

        $form->handleRequest($request);

        return $this->render('GatewayBundle:Default:update.html.twig', [
            'form' => $form->createView(),
            'gateway' => $gateway,
            'paymentForms' => $paymentForms,
            'paymentOptionModes' => $paymentOptionModes,
        ]);
    }

    public function saveAction(Request $request, $id = 'new')
    {
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_GATEWAY_CREATE']);
            $gateway = new Gateway();
        } else {
            $this->denyAccessUnlessGranted(['ROLE_GATEWAY_UPDATE']);
            $gateway = $this->getGatewayRepository()->find($id);
        }

        $paymentOptionValue = $request->get('Gateway')['paymentOption'];
        $paymentOption = $this->getPaymentOptionRepository()->find($paymentOptionValue);

        // $form = $this->createForm(GatewayType::class, $gateway);
        $formBuilder = $this->getFormFactory()->createBuilder(GatewayType::class, $gateway);
        $this->getGatewayFormManager()->getForm($paymentOption->getPaymentMode(), $gateway->getConfig(), [], $formBuilder->get('details')->get('config'));

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gateway = $form->getData();

            if ($id == 'new') {
                $gatewayData = $request->get('Gateway');
                $type = array_get($gatewayData, 'paymentOption');
                $paymentOption = $this->getEntityManager()->getReference(
                    PaymentOption::class,
                    $type
                );
                $gateway->setPaymentOptionEntity($paymentOption);
            } else {
                $this->getManager()->auditManualBalance($gateway);
            }

            $this->getGatewayRepository()->save($gateway);

            $this->getSession()->getFlashBag()->add('notifications', [
                'title' => $this->getTranslator()->trans(
                    'notification.title',
                    ['%name%' => $gateway->getName()],
                    'GatewayBundle'
                ),
                'message' => $this->getTranslator()->trans(
                    'notification.' . ($id === 'new' ? 'created' : 'updated'),
                    ['%name%' => $gateway->getName()],
                    'GatewayBundle'
                ),
            ]);

            return $this->redirectToRoute('gateway.update_page', ['id' => $gateway->getId()]);
        }

        return $this->redirect($request->headers->get('referer'), 307);
    }

    public function enableAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_CHANGE_STATUS']);
        $gateway = $this->getGatewayRepository()->find($id);
        if (is_null($gateway)) {
             throw new \Doctrine\ORM\NoResultException();
        } else if (!$gateway->getIsActive()) {
            $gateway->enable();
            $this->getGatewayRepository()->save($gateway);
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.enabled.title', [], 'GatewayBundle'),
                'message'   => $this->getTranslator()->trans('notification.enabled.message', ['%name%' => $gateway->getName()], 'GatewayBundle'),
            ];
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);

                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK,
                ]);
            }
        } else {
            throw new \Exception('Gateway is already enabled', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function suspendAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_CHANGE_STATUS']);
        $gateway = $this->getGatewayRepository()->find($id);
        if (is_null($gateway)) {
             throw new \Doctrine\ORM\NoResultException();
        } else if ($gateway->getIsActive()) {
            $gateway->suspend();
            $this->getGatewayRepository()->save($gateway);
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.suspended.title', [], 'GatewayBundle'),
                'message'   => $this->getTranslator()->trans('notification.suspended.message', ['%name%' => $gateway->getName() ], 'GatewayBundle'),
            ];
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);

                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK,
                ]);
            }
        } else {
            throw new \Exception('Gateway is already activated', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Get gateway repository.
     *
     * @return \DbBundle\Repository\GatewayRepository
     */
    protected function getGatewayRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Gateway');
    }

    /**
     * Get gateway manager.
     *
     * @return \GatewayBundle\Manager\GatewayManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('gateway.manager');
    }

    private function getGatewayFormManager(): \PaymentBundle\Manager\GatewayFormManager
    {
        return $this->get('payment.gateway_form_manager');
    }

    private function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository(PaymentOption::class);
    }

    private function getFormFactory(): \Symfony\Component\Form\FormFactory
    {
        return $this->get('form.factory');
    }
}
