<?php

namespace DWLBundle\Service;

use DbBundle\Entity\DWL;
use DbBundle\Entity\SubTransaction;

class DWLFileGeneratorService extends AbstractDWLService
{
    public function processDWl(DWL $dwl): array
    {
        try {
            $this->guardDWL($dwl);
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }

        $this->createFile($dwl);
        $start = 0;
        $limit = 100;
        $isFirst = true;
        $this->writeToFile($dwl, "{\n\"items\":[\n");
        do {
            $hasMoreRecord = false;
            $subTransactions = $this->getTransactionRepository()->getSubtransactionsByDWLId($dwl->getId(), $limit, $start);
            if (!empty($subTransactions)) {
                $hasMoreRecord = true;
                foreach ($subTransactions as $subTransaction) {
                    $jsonItemData = '';
                    if ($isFirst) {
                        $isFirst = false;
                    } else {
                        $jsonItemData .= ",\n";
                    }
                    $dwlItemData = $this->generateTransactionDWLItemData($subTransaction);
                    $jsonItemData .= \GuzzleHttp\json_encode($dwlItemData);
                    $this->writeToFile($dwl, $jsonItemData);
                }
                $start += $limit;
            }
            unset($subTranasctions);
        } while ($hasMoreRecord);
        $total = $dwl->getDetail('total');
        $this->writeToFile($dwl, "\n],");
        $this->writeToFile($dwl, "\"total\":" . \GuzzleHttp\json_encode($total));
        $this->writeToFile($dwl, "}");

        $this->createProgressFile($dwl);

        return ['success' => true];
    }

    private function createProgressFile(DWL $dwl)
    {
        $fileName = sprintf('dwl/progress_%s_v_%s.json', $dwl->getId(), $dwl->getVersion());
        $this->getMediaManager()->deleteFile($fileName);
        $this->getMediaManager()->createFile($fileName);
        $progressData = [
            '_v' => base64_encode($dwl->getUpdatedAt()->format('Y-m-d H:i:s')),
            'status' => $dwl->getStatus(),
            'process' => $dwl->getDetail('total.record'),
            'total' => $dwl->getDetail('total.record'),
        ];
        file_put_contents($this->getMediaManager()->getFilePath($fileName), \GuzzleHttp\json_encode($progressData));
    }

    private function generateTransactionDWLItemData(SubTransaction $subTransaction): array
    {
        return [
            "id" => $subTransaction->getCustomerProduct()->getId(),
            "username" => $subTransaction->getCustomerProduct()->getUserName(),
            "turnover" => $subTransaction->getDetail('dwl.turnover', 0),
            "gross" => $subTransaction->getDetail('dwl.gross', 0),
            "winLoss" => $subTransaction->getDetail('dwl.winLoss', 0),
            "commission" => $subTransaction->getDetail('dwl.commission', 0),
            "amount" => $subTransaction->getAmount(),
            "transaction" => [
                "id" => $subTransaction->getParent()->getId(),
                "subId" => $subTransaction->getId(),
            ],
            "calculatedAmount" => $subTransaction->getAmount(),
            "errors" => [],
            "customer" => [
                "balance" =>  $subTransaction->getDetail('dwl.customer.balance', null),
            ],
        ];
    }

    private function writeToFile(DWL $dwl, string $input, string $openFlag = 'a+')
    {
        $fileName = sprintf('dwl/%s_v_%s.json', $dwl->getId(), $dwl->getVersion());
        $file = $this->getMediaManager()->getFile($fileName);
        $openedFile = $file->openFile($openFlag);
        $openedFile->fwrite($input);
        $openedFile = null;
    }

    private function createFile(DWL $dwl)
    {
        $fileName = sprintf('dwl/%s_v_%s.json', $dwl->getId(), $dwl->getVersion());
        $this->getMediaManager()->deleteFile($fileName);
        $this->getMediaManager()->createFile($fileName);
    }

    private function guardDWL(DWL $dwl)
    {
        if (!$dwl->isCompleted()) {
            throw new \LogicException("Only completed dwl can be generate to file.");
        }
    }
}
