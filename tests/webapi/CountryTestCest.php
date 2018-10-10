<?php

use Step\Webapi\AuthenticatedWebApiTester;

class CountryTestCest
{
    public function _before(AuthenticatedWebApiTester $I)
    {
    }

    public function _after(AuthenticatedWebApiTester $I)
    {
    }

    // tests
    public function tryToGetAllCountries(AuthenticatedWebApiTester $I)
    {
        $I->loginUsingBearer();
        $I->sendGET('/en/api/countries?_format=json');

        // we should always have this assertion
        $I->seeResponseIsJson();

        // assert keys
        $I->seeResponseJsonMatchesJsonPath('$.total');
        $I->seeResponseJsonMatchesJsonPath('$.total_filtered');
        $I->seeResponseJsonMatchesJsonPath('$.limit');
        $I->seeResponseJsonMatchesJsonPath('$.page');
        $I->seeResponseJsonMatchesJsonPath('$.items');
        $I->seeResponseJsonMatchesJsonPath('$.items.[0].id');
        $I->seeResponseJsonMatchesJsonPath('$.items.[0].code');
        $I->seeResponseJsonMatchesJsonPath('$.items.[0].name');

        $I->seeResponseContainsJson(['total' => '242']);
        $I->seeResponseContainsJson(['total_filtered' => '242']);
        $I->seeResponseContainsJson(['limit' => '20']);
        $I->seeResponseContainsJson(['page' => '1']);
        $I->seeResponseContainsJson([
            'items' => [
                '0' => [
                    'id' => 1,
                    'code' => 'AF',
                    'name' => 'Afghanistan'
                ]
            ]
        ]);
    }

    /**
     * @dataProvider countryProvider
     */
    public function tryToGetACountryByCode(AuthenticatedWebApiTester $I, \Codeception\Example $country)
    {
        $I->loginUsingBearer();
        $I->sendGET('/en/api/countries/'. $country['code'] .'?_format=json');

        // we should always have this assertion
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath('$.id');
        $I->seeResponseJsonMatchesJsonPath('$.code');
        $I->seeResponseJsonMatchesJsonPath('$.name');


        $I->seeResponseContainsJson(['id' => $country['id']]);
        $I->seeResponseContainsJson(['code' => $country['code']]);
        $I->seeResponseContainsJson(['name' => $country['name']]);
    }

    /**
     * @return array
     */
    protected function countryProvider() // alternatively, if you want the function to be public, be sure to prefix it with `_`
    {
        return [
            [
                'id' => '1',
                'code' => 'AF',
                'name' => 'Afghanistan'
            ],
            [
                'id' => '2',
                'code' => 'AL',
                'name' => 'Albania'
            ],
            [
                'id' => '8',
                'code' => 'AQ',
                'name' => 'Antarctica'
            ],
            [
                'id' => '18',
                'code' => 'BD',
                'name' => 'Bangladesh'
            ],
            [
                'id' => '19',
                'code' => 'BB',
                'name' => 'Barbados'
            ],
            [
                'id' => '20',
                'code' => 'BY',
                'name' => 'Belarus'
            ],

        ];
    }
}
