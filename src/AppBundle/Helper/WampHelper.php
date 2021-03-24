<?php

namespace AppBundle\Helper;
//use Rx\Observable;
use Rx\Thruway\Client;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class WampHelper {

    private static $_client = null;

    private static function getClient() {
        if(self::$_client == null){
            $configs = self::getParameters();
            self::$_client = new Client($configs['websocket.url'], 'realm1');
        }
        return self::$_client;
    }
    
    protected static function getParameters(): array
    {
        try {
            $configs =  Yaml::parse(file_get_contents(__DIR__ . '/../../../app/config/parameters.yml'));
            return $configs['parameters'];
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
        }
    }
    
    private static function close(){
        if(self::$_client != null){
            self::$_client->close();
            self::$_client = null;
        }
    }

    public static function publish($url, $params = array()) {
        $client = self::getClient();
        
        $client->publish($url, $params);
        self::close();
    }

}
