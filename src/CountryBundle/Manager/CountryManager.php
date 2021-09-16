<?php

namespace CountryBundle\Manager;

use AppBundle\Manager\AbstractManager;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Simple\RedisCache;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Exceptions\FormValidationException;
use Symfony\Component\Validator\Constraints\DateTime;

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
        $countries = $this->getCountries();
        $countries['Unknown'] = ['code' => null, 'name' => 'Unknown'];
        return $countries;
    }

    public function getCountryByCallingCode(string $callingCode)
    {
        $countries = $this->getCountries();
        
        $filteredCountries = array_filter($countries, function ($data) use ($callingCode) {
            return in_array($callingCode, $data['callingCodes']);
        });

        if (count($filteredCountries) < 1) {
            return 'Unknown';
        }

        return reset($filteredCountries)['code'];
    }

    public function getCountries()
    {
        $cacheKey = base64_encode(__METHOD__);
        $cache = new RedisCache($this->get('snc_redis.default'));
        if($cache->has($cacheKey) && $item = $cache->get($cacheKey)) 
            return $item;
        
        $client = new Client([
            'timeout' => 60,
            'headers' => ['Accept-Encoding' => 'gzip, deflate'],
            'verify' => false
        ]);
        $response = $client->get('https://restcountries.eu/rest/v2/all');

        $data = json_decode($response->getBody(), true);
        $countries =  array_reduce($data, function ($carry, $datum) {
            $carry[$datum['alpha2Code']] = [
                'code' => $datum['alpha2Code'],
                'name' => $datum['name'],
                'callingCodes' => $datum['callingCodes']
            ];

            return $carry;
        }, []);

        if($response->getStatusCode() == 200){
            $cache->set($cacheKey, $countries, \DateInterval::createFromDateString('30 days')->s);
        }

        return $countries;
    }

    public function getCountriesNameCodeKeyPair()
    {
        $countries = $this->getCountries();
        array_walk($countries, function(&$item, $key){
           $item = $item['name'];
        });

        return array_flip($countries);
    }

    public function getCountryByCode(?string $code): string
    {
        if ($code === null) return 'Unknown';
        
        $countries = $this->getCountries();
        return $countries[$code]['name'];
    }
    
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Country');
    }
}
