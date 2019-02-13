# How to properly generate CSV from large data set without eating too much memory
this will prevent 504 (gateway timeout) errors

### In your controller 
use StreamedResponse to "chunk" the csv as it is being generated.
this allows the browser to start the download immediately instead of waiting for the Webserver to create the csv file completely
```php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;


public function exportAction(Request $request) 
{
        $response = new StreamedResponse(function () use ($currencyEntity, $reportStartDate, $reportEndDate, $customerNameQueryString, $hideInactiveMembers) {
            $this->transactionManager->printCsvReport();
        });
}

```

### In your service/manager
echo/print the data as it is being fetched (using pointers) from the database.
doing this prevents memory from spiking because we will not be saving them in a very large array

```php

class TransactionManager 
{
  ...
  public function printCsvReport()
  {
        // Query is an instance of Doctrine\ORM\AbstractQuery which has IterableResult
        $query = $this->getMemberProductReportQuery($product, $currencyEntity, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString);
        $iterableResult = $query->iterate($parameters = null, $hydrationMode = Query::HYDRATE_ARRAY);
        foreach ($iterableResult as $row) {
            $transaction = array_pop($row);
            
            echo $transaction['number'] . ',';
            echo $transaction['someOtherField'] . ',';
            echo $transaction['someOtherField'];
            echo "\n";
         }
  }
  ...
}
```

you can also read about this here 
https://medium.com/@mark.anthony.r.rosario/how-to-properly-use-doctrine-orm-to-generate-a-large-csv-download-without-consuming-too-much-memory-1edeeab10407
