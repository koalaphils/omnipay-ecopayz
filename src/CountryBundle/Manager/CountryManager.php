<?php

namespace CountryBundle\Manager;

use AppBundle\Manager\AbstractManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Exceptions\FormValidationException;

class CountryManager extends AbstractManager
{
    public function handleForm(Form $form, Request $request)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $country = $form->getData();
            $this->getRepository()->save($country);
            
            return $country;
        }
        
        throw new FormValidationException($form);
    }
    
    public function getCountryList($filters = null)
    {
        $results = [];

        if (array_get($filters, 'datatable', 0)) {
            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }

            $results['data'] = $this->getRepository()->getCountryList($filters);

            if (array_get($filters, 'route', 0)) {
                $results['data'] = array_map(function ($data) {
                    $data['country'] = $data;
                    $data['routes'] = [
                        'update' => $this->getRouter()->generate('country.update_page', ['id' => $data['country']['id']]),
                        'view' => $this->getRouter()->generate('country.view_page', ['id' => $data['country']['id']]),
                        'save' => $this->getRouter()->generate('country.save', ['id' => $data['country']['id']]),
                    ];

                    return $data;
                }, $results['data']);
            }

            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getCountryListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getCountryListAllCount();

        } elseif (array_get($filters, 'select2', 0)) {
            $results['items'] = array_map(function ($group) use ($filters) {
                return [
                    'id' => $group[array_get($filters, 'idColumn', 'id')],
                    'text' => $group['name'],
                ];
            }, $this->getRepository()->getCountryList($filters));

            $results['recordsFiltered'] = $this->getRepository()->getCountryListFilterCount($filters);
        } else {
            $results = $this->getRepository()->getCountryList($filters);
        }

        return $results;
    }

    /**
     * Get country repository.
     *
     * @return \DbBundle\Repository\CountryRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Country');
    }
}
