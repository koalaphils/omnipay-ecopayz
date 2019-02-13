<?php

namespace AppBundle\Component;

class ZTableResponse
{
    private static $defaultLimit = 10;
    private static $defaultpage = 1;
    private static $defaultRecords = [];
    private static $defaultRecordsFilteredCount = 0;
    private static $defailtRecordsTotalCount = 0;

    public function getResponseAsArray(): array
    {
        $response['limit'] = self::$defaultLimit;
        $response['page'] = self::$defaultpage;
        $response['records'] = self::$defaultRecords;
        $response['recordsFiltered'] = self::$defaultRecordsFilteredCount;
        $response['recordsTotal'] = self::$defailtRecordsTotalCount;

        return $response;
    }
}

