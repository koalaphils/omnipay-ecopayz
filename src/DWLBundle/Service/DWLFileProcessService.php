<?php

namespace DWLBundle\Service;

use DbBundle\Entity\DWL;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use DbBundle\Entity\CustomerProduct;
use AppBundle\ValueObject\Number;

class DWLFileProcessService extends AbstractDWLService
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    public function processDWL(DWL $dwl): array
    {
        try {
            $this->guardProcessDWL($dwl);
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'error');

            return ['success' => false, 'error' => $e->getMessage()];
        }

        $this->log('Update DWL status to Processing');
        $this->updateDWL($dwl, DWL::DWL_STATUS_PROCESSING);

        $this->log('Start Processing file');
        $csvFileName = sprintf("dwl/%s_v_%s.csv", $dwl->getId(), $dwl->getVersion());
        $processedData = $this->processCsvFile($dwl, $csvFileName);
        $this->log('Process Done');
        $this->log(sprintf('Total Items %s', $processedData['total']['record']));

        $this->log('Writing to json file');
        $this->writeToJsonFile($dwl, \GuzzleHttp\json_encode($processedData));
        $this->log('Done writing to json file');

        $this->log('Saving DWL');
        $dwl->setDetail('total', $processedData['total']);
        $this->updateDWL($dwl, DWL::DWL_STATUS_PROCESSED);
        $this->updateProcess($dwl, $processedData['total']['record'], $processedData['total']['record']);
        $this->log('Done saving DWL');

        $this->log('Deleting CSV');
        $this->getMediaManager()->deleteFile($csvFileName);
        $this->log('Done deleting CSV');

        return ['success' => true];
    }

    private function processCsvFile(DWL $dwl, string $filename): array
    {
        $file = $this->getMediaManager()->getFile($filename);
        $openedCsvFile = $file->openFile();

        $processed = 0;
        $currentLine = 1;
        $totalRecords = $this->getTotal($openedCsvFile);
        $processedData = [
            'items' => [],
            'total' => [
                'turnover' => 0,
                'grossCommission' => 0,
                'memberWinLoss' => 0,
                'memberCommission' => 0,
                'memberAmount' => 0,
                'record' => $totalRecords,
            ],
        ];
        $existingUsernames = [];

        while ($processed < $totalRecords) {
            $itemInfo = $this->getFileLineInfo($openedCsvFile, $currentLine);
            $item = $this->generateItem($dwl, $itemInfo, $existingUsernames);
            $processedData['items'][] = $item;

            if (!in_array($item['username'], $existingUsernames)) {
                $existingUsernames[] = $item['username'];
            }

            $processedData['total']['turnover'] = (new Number($processedData['total']['turnover']))->plus($item['turnover'])->__toString();
            $processedData['total']['grossCommission'] = (new Number($processedData['total']['grossCommission']))->plus($item['gross'])->__toString();
            $processedData['total']['memberWinLoss'] = (new Number($processedData['total']['memberWinLoss']))->plus($item['winLoss'])->__toString();
            $processedData['total']['memberCommission'] = (new Number($processedData['total']['memberCommission']))->plus($item['commission'])->__toString();
            $processedData['total']['memberAmount'] = (new Number($processedData['total']['memberAmount']))->plus($item['amount'])->__toString();

            ++$currentLine;
            ++$processed;

            $this->updateProcess($dwl, $processed, $totalRecords);
        };

        $openedCsvFile = null;

        return $processedData;
    }

    private function writeToJsonFile(DWl $dwl, string $data)
    {
        $jsonFileName = sprintf("dwl/%s_v_%s.json", $dwl->getId(), $dwl->getVersion());
        $jsonFileName = $this->getMediaManager()->getFilePath($jsonFileName);
        file_put_contents($jsonFileName, $data);
    }

    private function generateItem(DWL $dwl, array $fileInfo, array $existingUsernames): array
    {
        list($username, $turnover, $gross, $winLoss, $commission, $amount) = $fileInfo;
        $info = [
            'id' => null,
            'username' => $username,
            'turnover' => $turnover,
            'gross' => $gross,
            'winLoss' => $winLoss,
            'commission' => $commission,
            'amount' => $amount,
            'calculatedAmount' => null,
            'transaction' => [
                'id' => null,
                'subId' => null,
            ],
        ];

        if (is_numeric($winLoss) && is_numeric($commission)) {
            $info['calculatedAmount'] = Number::add($winLoss, $commission)->__toString();
        }

        $customerProduct = $this->getCustomerProductRepository()->findByUsernameProductAndCurrency(
            $username,
            $dwl->getProduct()->getId(),
            $dwl->getCurrency()->getId()
        );

        if ($customerProduct !== null) {
            $info['id'] = $customerProduct->getId();
        }

        if ($dwl->getId() && $customerProduct !== null) {
            $subtransaction = $this->getTransactionRepository()->getSubtransactionByProductAndDwlId(
                $customerProduct->getId(),
                $dwl->getId()
            );
            if ($subtransaction !== null) {
                array_set($info, 'transaction.id', $subtransaction->getParent()->getId());
                array_set($info, 'transaction.subId', $subtransaction->getId());
                array_set(
                    $info,
                    'customer.balance',
                    $subtransaction->getDetail('dwl.customer.balance', $subtransaction->getCustomerProduct()->getBalance())
                );
            }
        }

        $info['errors'] = $this->validateProduct($dwl, $customerProduct, $info, $existingUsernames);

        return $info;
    }

    private function validateProduct(DWL $dwl, ?CustomerProduct $customerProduct, array $info, array $existingUsernames): array
    {
        $errors = [];

        if (in_array($info['username'], $existingUsernames)) {
            $errors['username'] = "Duplicate Username";
        } elseif ($info['id'] === null) {
            $errors['username'] = "Username does not exists";
        } elseif ($dwl->getProduct()->getId() !== $customerProduct->getProduct()->getId()) {
            $errors['username'] = 'Product does not match';
        } elseif ($dwl->getCurrency()->getId() !== $customerProduct->getCustomer()->getCurrency()->getId()) {
            $errors['username'] = 'Currency does not match';
        }

        if (!is_numeric($info['turnover'])) {
            $errors['turnover'] = 'Invalid Value';
        }

        if (!is_numeric($info['gross'])) {
            $errors['gross'] = 'Invalid Value';
        }

        if (!is_numeric($info['winLoss'])) {
            $errors['winLoss'] = 'Invalid Value';
        }

        if (!is_numeric($info['commission'])) {
            $errors['commission'] = 'Invalid Value';
        }

        if (!is_numeric($info['amount'])) {
            $errors['amount'] = 'Invalid Value';
        } elseif ((new Number($info['amount']))->notEqual($info['calculatedAmount'])) {
            $errors['amount'] = 'Incorrect Amount';
        }

        return $errors;
    }

    private function getFileLineInfo(\SplFileObject $file, int $line)
    {
        $file->seek($line);

        return $file->fgetcsv();
    }

    private function generateJsonFile(DWL $dwl): File
    {
        $jsonFileName = sprintf("dwl/%s_v_%s.json", $dwl->getId(), $dwl->getVersion());
        if ($this->getMediaManager()->isFileExists($jsonFileName)) {
            $this->getMediaManager()->deleteFile($jsonFileName);
            $this->log('File deleted ' . $jsonFileName, 'info');
        }
        $this->getMediaManager()->createFile($jsonFileName);
        $this->log('File created ' . $jsonFileName, 'info');

        return $jsonFileName;
    }

    private function guardProcessDWL(DWL $dwl)
    {
        if ($dwl->getStatus() !== DWL::DWL_STATUS_UPLOADED) {
            throw new \LogicException("DWL can't be processed since it was not newly uploaded");
        }

        $csvFileName = sprintf("dwl/%s_v_%s.csv", $dwl->getId(), $dwl->getVersion());
        if (!$this->getMediaManager()->isFileExists($csvFileName)) {
            throw new FileNotFoundException($csvFileName);
        }
    }

    private function getTotal(\SplFileObject $file)
    {
        $total = 0;
        while (!$file->eof()) {
            $file->current();
            ++$total;
            $file->next();
        }
        $total -= 3;
        $file->rewind();

        return $total;
    }
}
