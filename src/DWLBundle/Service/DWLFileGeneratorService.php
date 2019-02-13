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
            $totalRecord = $this->getSubTransactionRepository()->getTotalSubTransactionForDwl($dwl->getId());
            $dwl->setTotalRecord($totalRecord);
            $this->getEntityManager()->persist($dwl);
            $this->getEntityManager()->flush($dwl);
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }

        $this->createFile($dwl);
        $isFirst = true;
        $this->writeToFile($dwl, "{\n\"items\":[\n");

        $subTransactions = $this->getTransactionRepository()->getSubtransactionsByDWLIteratable($dwl->getId());
        foreach ($subTransactions as $record) {
            $subTransaction = $record[0];
            if ($subTransaction->isDwlExcludeInList()) {
                continue;
            } else {
                $jsonItemData = '';
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    $jsonItemData .= ",\n";
                }
                $dwlItemData = $this->getDWLManager()->generateTransactionDWLItemData($subTransaction);
                $jsonItemData .= \GuzzleHttp\json_encode($dwlItemData);
                $this->writeToFile($dwl, $jsonItemData);
            }
        }

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
