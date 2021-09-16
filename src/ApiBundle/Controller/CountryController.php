<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Collection\Collection;

class CountryController extends AbstractController
{
     /**
     * @ApiDoc(
     *  description="Get all countries",
     *  section="Country",
     *  views={"piwi", "default"},
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
      *     {"name"="withphonecode", "dataType"="integer", "description"="true = 1, false = 0"},
      *     {"name"="order[0][column]", "dataType"="string"},
      *     {"name"="order[0][dir]", "dataType"="string", "description"="ASC, DESC"}
     *  },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function countryListAction(Request $request)
    {
        $filters = [];
        $filters['limit'] = $request->get('limit', 20);
        if ($filters['limit'] !== 'all') {
            $filters['offset'] = (((int) $request->get('page', 1))-1) * $filters['limit'];
        }

        if ($request->get('search', null) !== null) {
            $filters['search'] = $request->get('search', '');
        }

        if ($request->query->has('tags')) {
            $filters['tags'] = $request->query->get('tags');
        }

        if ($request->query->has('order')) {
            $filters['order'] = $request->get('order');
        }

        $filters['withphonecode'] = $request->get('withphonecode', 0);
        
        $countries = $this->getCountryRepository()->getCountryList($filters, \Doctrine\ORM\Query::HYDRATE_OBJECT);
        $total = $this->getCountryRepository()->getCountryListAllCount();
        $totalFiltered = $this->getCountryRepository()->getCountryListFilterCount($filters);

        if ($filters['limit'] === 'all') {
            $collection = new Collection($countries, $total, $totalFiltered, count($countries), $request->get('page', 1));
        } else {
            $collection = new Collection($countries, $total, $totalFiltered, $filters['limit'], $request->get('page', 1));
        }

        $countries = $this->get('country.manager')->getCountries();

        return new JsonResponse(['items' => array_values($countries)]);
    }
    
    /**
     * @ApiDoc (
     *  section="Country",
     *  description="Get specific country",
     *  views={"piwi", "default"},
     *  output={
     *      "class"="DbBundle\Entity\Country",
     *      "parsers"={ "ApiBundle\Parser\CollectionParser", "ApiBundle\Parser\JmsMetadataParser" },
     *      "groups"={ "Default" ,"API" }
     *  },
     *  headers={
     *      { "name"="Authorization", "description"="Bearer <access_token>" }
     *  }
     * )
     */
    public function countryAction(string $code)
    {
        $country = $this->get('country.manager')->getCountries()[$code];
        
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
