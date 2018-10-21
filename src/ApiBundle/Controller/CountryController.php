<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Collection\Collection;

class CountryController extends AbstractController
{
     /**
     * @ApiDoc(
     *  description="Get all countries",
     *  output={
     *      "class"="ArrayCollection<DbBundle\Entity\Country>",
     *      "parsers"={ "ApiBundle\Parser\CollectionParser", "ApiBundle\Parser\JmsMetadataParser" },
     *      "groups"={ "API" }
     *  },
     *  filters={
     *      {"name"="search", "dataType"="string"},
     *      {"name"="limit", "dataType"="integer"},
     *      {"name"="page", "dataType"="integer"},
     *      {"name"="tags", "dataType"="string"},
     *  }
     * )
     */
    public function countryListAction(Request $request)
    {
        $filters = [];
        $filters['limit'] = $request->get('limit', 20);
        $filters['offset'] = (((int) $request->get('page', 1))-1) * $filters['limit'];

        if ($request->get('search', null) !== null) {
            $filters['search'] = $request->get('search', '');
        }

        if ($request->query->has('tags')) {
            $filters['tags'] = $request->query->get('tags');
        }
        
        $countries = $this->getCountryRepository()->getCountryList($filters, \Doctrine\ORM\Query::HYDRATE_OBJECT);
        $total = $this->getCountryRepository()->getCountryListAllCount();
        $totalFiltered = $this->getCountryRepository()->getCountryListFilterCount($filters);
        $collection = new Collection($countries, $total, $totalFiltered, $filters['limit'], $request->get('page', 1));
        $view = $this->view($collection);
        $view->getContext()->setGroups(['Default', 'API', 'items' => ['Default', 'API']]);

        return $view;
    }
    
    /**
     * @ApiDoc (
     *  description="Get specific country",
     *  output={
     *      "class"="DbBundle\Entity\Country",
     *      "parsers"={ "ApiBundle\Parser\CollectionParser", "ApiBundle\Parser\JmsMetadataParser" },
     *      "groups"={ "Default" ,"API" }
     *  }
     * )
     */
    public function countryAction(string $code)
    {
        $country = $this->getCountryRepository()->findOneBy(['code' => $code]);
        
        if ($country === null) {
            throw $this->createNotFoundException('Country not found');
        }
        
        return $view = $this->view($country);
    }
    
    protected function getCountryRepository(): \DbBundle\Repository\CountryRepository
    {
        return $this->getRepository(\DbBundle\Entity\Country::class);
    }
}