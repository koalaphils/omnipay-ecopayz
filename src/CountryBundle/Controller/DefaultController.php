<?php

namespace CountryBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CountryBundle\Form\CountryType;
use DbBundle\Entity\Country;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_COUNTRY_VIEW']);
        
        $countryForm = $this->createForm(CountryType::class, null, [
            'action' => $this->getRouter()->generate('country.save'),
        ]);

        return $this->render('CountryBundle:Default:index.html.twig', ['form' => $countryForm->createView()]);
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_COUNTRY_VIEW']);
        $filters = $request->request->all();
        $results = $this->getManager()->getCountryList($filters);

        return new JsonResponse($results, JsonResponse::HTTP_OK);
    }

    public function viewAction(Request $request, $id)
    {
    }

    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_COUNTRY_CREATE']);
        $form = $this->createForm(CountryType::class, null, [
            'action' => $this->getRouter()->generate('country.save'),
        ]);
        $form->handleRequest($request);

        return $this->render('CountryBundle:Default:create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_COUNTRY_UPDATE']);
        $this->getMenuManager()->setActive('country.list');

        $country = $this->getCountryRepository()->getWithCurrency($id);

        $form = $this->createForm(CountryType::class, $country, [
            'action' => $this->getRouter()->generate('country.save', ['id' => $id]),
        ]);
        $form->handleRequest($request);

        return $this->render('CountryBundle:Default:update.html.twig', [
            'form' => $form->createView(),
            'country' => $country,
        ]);
    }
    
    

    public function saveAction(Request $request, $id = 'new')
    {
        $response = ['success' => false];
        $statusCode = Response::HTTP_OK;
        
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_COUNTRY_CREATE']);
            $country = new Country();
            $statusCode = Response::HTTP_CREATED;
        } else {
            $this->denyAccessUnlessGranted(['ROLE_COUNTRY_UPDATE']);
            $country = $this->getCountryRepository()->find($id);
        }

        $form = $this->createForm(CountryType::class, $country);
        try {
            $country = $this->getManager()->handleForm($form, $request);
            $response['success'] = true;
            $response['data'] = $country;
        } catch (\AppBundle\Exceptions\FormValidationException $e) {
            $response['errors'] = $e->getErrors();
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        }
        
        return $this->response($request, $response, [], $statusCode);
    }

    /**
     * Get country repository.
     *
     * @return \DbBundle\Repository\CountryRepository
     */
    protected function getCountryRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Country');
    }

    /**
     * Get country manager.
     *
     * @return \CountryBundle\Manager\CountryManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('country.manager');
    }
}
