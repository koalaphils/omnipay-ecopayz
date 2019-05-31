<?php declare(strict_types=1);

namespace Application\Migrations;

use DbBundle\Entity\Transaction;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190531062918 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $setting = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('setting')
            ->where('setting_code = "pinnacle"')
            ->execute();
        $pinnacleSetting = ['transaction' => ['deposit' => ['status' => 2], 'withdraw' => ['status' => 4]]];
        if ($data = $setting->fetch()) {
            $pinnacleSetting = json_decode($data['setting_value'], true);
        }

        $this->connection->getWrappedConnection()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $transactions = $this->connection->createQueryBuilder()
            ->select('transaction_id, transaction_status, transaction_type, transaction_is_voided, transaction_other_details, subtransaction_id, subtransaction_details, subtransaction_type')
            ->from('sub_transaction', 's')
            ->innerJoin('s', 'transaction', 't', 't.transaction_id = s.subtransaction_transaction_id')
            ->execute();
        while ($transaction = $transactions->fetch()) {
            $transactionDetail = json_decode($transaction['transaction_other_details'], true);
            $subtransactionDetail = json_decode($transaction['subtransaction_details'], true);
            if (array_get($subtransactionDetail, 'pinnacle.transacted', false)) {
                if ($transaction['transaction_type'] == Transaction::TRANSACTION_TYPE_DEPOSIT && empty(array_get($subtransactionDetail, 'pinnacle.transaction_dates.deposit', []))) {
                    if (array_has($transactionDetail, 'transaction_dates.' . $pinnacleSetting['transaction']['deposit']['status'])) {
                        array_set($subtransactionDetail, 'pinnacle.transaction_dates.deposit', ['status' => $pinnacleSetting['transaction']['deposit']['status'], 'date' => array_get($transactionDetail, 'transaction_dates.' . $pinnacleSetting['transaction']['deposit']['status'])]);
                    }
                } elseif ($transaction['transaction_type'] == Transaction::TRANSACTION_TYPE_WITHDRAW && empty(array_get($subtransactionDetail, 'pinnacle.transaction_dates.withdraw', []))) {
                    if (array_has($transactionDetail, 'transaction_dates.' . $pinnacleSetting['transaction']['withdraw']['status'])) {
                        array_set($subtransactionDetail, 'pinnacle.transaction_dates.withdraw', ['status' => $pinnacleSetting['transaction']['withdraw']['status'], 'date' => array_get($transactionDetail, 'transaction_dates.' . $pinnacleSetting['transaction']['withdraw']['status'])]);
                    }
                }

                if (
                    $transaction['transaction_type'] == Transaction::TRANSACTION_TYPE_WITHDRAW
                    && $transaction['transaction_status'] == Transaction::TRANSACTION_STATUS_DECLINE
                    && empty(array_get($subtransactionDetail, 'pinnacle.transaction_dates.deposit', []))
                ) {
                    if (array_has($transactionDetail, 'transaction_dates.' . Transaction::TRANSACTION_STATUS_DECLINE)) {
                        array_set($subtransactionDetail, 'pinnacle.transaction_dates.deposit', ['status' => Transaction::TRANSACTION_STATUS_DECLINE, 'date' => array_get($transactionDetail, 'transaction_dates.' . Transaction::TRANSACTION_STATUS_DECLINE)]);
                    }
                }

                if ($transaction['transaction_is_voided']) {
                    if ($transaction['transaction_type'] == Transaction::TRANSACTION_TYPE_DEPOSIT && empty(array_get($subtransactionDetail, 'pinnacle.transaction_dates.withdraw', []))) {
                        if (array_has($transactionDetail, 'transaction_dates.void')) {
                            array_set($subtransactionDetail, 'pinnacle.transaction_dates.withdraw', ['status' => 'voided', 'date' => array_get($transactionDetail, 'transaction_dates.void')]);
                        }
                    } elseif ($transaction['transaction_type'] == Transaction::TRANSACTION_TYPE_WITHDRAW && empty(array_get($subtransactionDetail, 'pinnacle.transaction_dates.deposit', []))) {
                        if (array_has($transactionDetail, 'transaction_dates.void')) {
                            array_set($subtransactionDetail, 'pinnacle.transaction_dates.deposit', ['status' => 'voided', 'date' => array_get($transactionDetail, 'transaction_dates.void')]);
                        }
                    }
                }
            }
            $this->addSql('UPDATE sub_transaction SET subtransaction_details = ? WHERE subtransaction_id = ?', [json_encode($subtransactionDetail), $transaction['subtransaction_id']]);
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
