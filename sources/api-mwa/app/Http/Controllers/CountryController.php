<?php

namespace App\Http\Controllers;

use App\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * get IP address
     *
     * @return string
     */
    private function getIP(){

        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        {
          $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
          $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
          $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;    
    }

    /**
     * get country phone codes
     *
     * @return response
     */
    public function selectCountry()
    {        
        $sources = app('db')->select("select country_phone_code from country WHERE country_phone_code <> '' order by country_phone_code desc");        
        $ip = $this->getIP();
        $api = 'http://www.geoplugin.net/json.gp';

        $res = $this->callApi($api, array('ip'=>$ip), 'GET');
        $res = json_decode($res, true);
        $country_code = $res['geoplugin_countryCode'];        
        $sql = "select country_phone_code from country where country_code = '". $country_code ."'";
       
        $phone_code = app('db')->select($sql);
        $phone_code = (array) $phone_code[0];
        $phone_code = $phone_code['country_phone_code'];

        // convert
        $phone_codes = array();
        foreach($sources as $val){            
            $phone_codes[] = $val->country_phone_code;            
        }
        
        return response()->json(array('error' => false, 'status'=>200, 'sources' => $phone_codes, 'selected' => $phone_code));
        
    }
}
