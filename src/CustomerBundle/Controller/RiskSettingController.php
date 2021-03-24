<?php

namespace CustomerBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Controller\AbstractController;
use AppBundle\Exceptions\FormValidationException;
use CustomerBundle\Form\RiskSettingType;
use DbBundle\Entity\RiskSetting;

class RiskSettingController extends AbstractController
{
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_RISK_SETTING_VIEW']);
        return $this->render('CustomerBundle:RiskSetting:list.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_RISK_SETTING_VIEW']);
        $filters = $request->request->all();
        $filters = array_merge($filters, $request->query->all());
        $data = $this->getManager()->filter($filters);

        return $this->response($request, $data, ['groups' => ['Default', '_link']]);
    }

    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_RISK_SETTING_CREATE']);
        
        $riskSetting = new RiskSetting();
        $products = $this->getAllProducts();

        $form = $this->createForm(RiskSettingType::class, $riskSetting, [
            'action' => $this->generateUrl('customer.risk_setting.save', ['id' => 'new']),
            'method' => 'POST',
        ]);

        return $this->render('CustomerBundle:RiskSetting:create.html.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request, int $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_RISK_SETTING_UPDATE']);

        $riskSetting = $this->getManager()->getRiskSetting($id);
        $riskSetting->preserveOriginal();
        $products = $this->getAllProducts();
        $riskSetting->addAllAvailableProducts($products);
        
        if ($riskSetting === null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(RiskSettingType::class, $riskSetting, [
            'action' => $this->generateUrl('customer.risk_setting.save', ['id' => $id]),
            'method' => 'POST',
        ]);

        return $this->render('CustomerBundle:RiskSetting:update.html.twig', ['form' => $form->createView()]);
    }

    public function saveAction(Request $request, $id = 'new')
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_RISK_SETTING_UPDATE']);

        $riskSetting = $this->getRiskSetting($id);
        $riskSetting->preserveOriginal();
        $form = $this->createForm(RiskSettingType::class, $riskSetting, []);
        $form->handleRequest($request);

        try {
            if ($this->isFormProcessable($form)) {
                $this->getManager()->saveRiskSetting($riskSetting);
        
                $response = [
                    'success' => true,
                    'data' => $riskSetting,
                    'link' => $this->generateUrl('customer.risk_setting')
                ];
            }
        } catch (FormValidationException $exception) {
            $response = [
                'success' => false,
                'errors' => $exception->getErrors(),
            ];
        }

        return new JsonResponse($response);
    }

    public function activateAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_RISK_SETTING_UPDATE']);
        $riskSetting = $request->request->get('riskSettingId');
        $message = $this->getManager()->activate($riskSetting);

        if (!$request->isXmlHttpRequest()) {
            $this->getSession()->getFlashBag()->add('notifications', $message);
            return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
        } else {
            return new JsonResponse([
                '__notifications' => $message, JsonResponse::HTTP_OK,
            ]);
        }
    }

    public function suspendAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_RISK_SETTING_SUSPEND']);
        $riskSettingId = $request->request->get('riskSettingId');
        $message = $this->getManager()->suspend($riskSettingId);

        if (!$request->isXmlHttpRequest()) {
            $this->getSession()->getFlashBag()->add('notifications', $message);
            return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
        } else {
            return new JsonResponse([
                '__notifications' => $message, JsonResponse::HTTP_OK,
            ]);
        }
    }

    protected function getRiskSetting($id): RiskSetting
    {
        if ($id === 'new') {
            $riskSetting = new RiskSetting();
        } else {
            $riskSetting = $this->getManager()->getRiskSetting($id);
        }

        return $riskSetting;
    }

    protected function getManager()
    {
        return $this->get('customer.riskSettingService');
    }

    protected function getSettingManager()
    {
        return $this->getContainer()->get('app.setting_manager');
    }

    public function getAllProducts(): array
    {
        $repo = $this->get('doctrine.orm.default_entity_manager')->getRepository('DbBundle:Product');

        return $repo->findAll();
    }
}
