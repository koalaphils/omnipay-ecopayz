<?php

namespace CurrencyBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use DbBundle\Entity\Currency;
use CurrencyBundle\Form\CurrencyType;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        // off this feature
        return $this->redirectToRoute("app.dashboard_page");
        
        $this->denyAccessUnlessGranted(['ROLE_CURRENCY_VIEW']);
        $form = $this->createForm(CurrencyType::class, null, [
            'action' => $this->getRouter()->generate('currency.save'),
        ]);
        $baseCurrency = $this->getManager()->getBaseCurrency();

        return $this->render('CurrencyBundle:Default:index.html.twig', [
            'form' => $form->createView(),
            'hasRate' => true,
            'baseCurrency' => $baseCurrency,
        ]);
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CURRENCY_VIEW']);
        $filters = $request->request->all();
        $results = $this->getManager()->getCurrencyList($filters);

        return new JsonResponse($results, JsonResponse::HTTP_OK);
    }

    public function viewAction(Request $request, $id)
    {
    }

    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CURRENCY_CREATE']);
        $form = $this->createForm(CurrencyType::class, null, [
            'action' => $this->getRouter()->generate('currency.save'),
        ]);
        $form->handleRequest($request);
        $baseCurrency = $this->getCurrencyRepository()->find($this->getSettingManager()->getSetting('currency.base'));

        return $this->render('CurrencyBundle:Default:create.html.twig', [
            'form' => $form->createView(),
            'baseCurrency' => $baseCurrency,
            'hasRate' => true,
        ]);
    }

    public function updateAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CURRENCY_UPDATE']);
        $this->getMenuManager()->setActive('currency.list');

        $currency = $this->getCurrencyRepository()->find($id);

        $form = $this->createForm(CurrencyType::class, $currency, [
            'action' => $this->getRouter()->generate('currency.save', ['id' => $id]),
        ]);
        $form->handleRequest($request);

        $baseCurrency = $this->getCurrencyRepository()->find($this->getSettingManager()->getSetting('currency.base'));

        return $this->render('CurrencyBundle:Default:update.html.twig', [
            'form' => $form->createView(),
            'hasRate' => $form->has('rate'),
            'currency' => $currency,
            'baseCurrency' => $baseCurrency,
        ]);
    }

    public function saveAction(Request $request, $id = 'new')
    {
        $response = ['success' => false];
        $statusCode = Response::HTTP_OK;

        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_CURRENCY_CREATE']);
            $currency = new Currency();
            $statusCode = Response::HTTP_CREATED;
        } else {
            $this->denyAccessUnlessGranted(['ROLE_CURRENCY_UPDATE']);
            $currency = $this->getCurrencyRepository()->find($id);
        }

        $form = $this->createForm(CurrencyType::class, $currency);
        try {
            $currency = $this->getManager()->handleForm($form, $request);
            $response['success'] = true;
            $response['data'] = $currency;
        } catch (\AppBundle\Exceptions\FormValidationException $e) {
            $response['errors'] = $e->getErrors();
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        return $this->response($request, $response, [], $statusCode);
    }

    /**
     * Get currency repository.
     *
     * @return \DbBundle\Repository\CurrencyRepository
     */
    protected function getCurrencyRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Currency');
    }

    /**
     * Get currency manager.
     *
     * @return \CurrencyBundle\Manager\CurrencyManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('currency.manager');
    }
}
