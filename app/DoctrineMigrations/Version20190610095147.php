<?php declare(strict_types=1);

namespace Application\Migrations;

use DbBundle\Entity\User;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190610095147 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->connection->getWrappedConnection()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $users = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('`user`', 'u')
            ->where('u.user_type = :userType')
            ->setParameter('userType', User::USER_TYPE_MEMBER)
            ->execute()
        ;
        while ($user = $users->fetch()) {
            $userDetail = json_decode($user['user_preferences'], true);
            $data = [
                'ip' => $userDetail['ipAddress'] ?? '',
                'locale' => $userDetail['locale'] ?? '',
                'referrer_url' => $userDetail['referrer'] ?? '',
                'referrer_origin_url' => $userDetail['originUrl'] ?? '',
            ];
            $this->addSql(
                'UPDATE `customer` SET `customer_other_details`=JSON_SET(`customer_other_details`, "$.registration", CAST(? AS JSON)) WHERE customer_user_id = ?',
                [json_encode($data), $user['user_id']]
            );
            $this->addSql(
                'UPDATE `user` SET `user_preferences`=JSON_REMOVE(`user_preferences`, "$.ipAddress", "$.locale", "$.referrer", "$.originUrl") WHERE user_id = ?',
                [$user['user_id']]
            );
        }
    }

    public function down(Schema $schema) : void
    {
        $this->connection->getWrappedConnection()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $members = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('`customer`', 'c')
            ->execute()
        ;

        while ($member = $members->fetch()) {
            $memberDetail = json_decode($member['customer_other_details'], true);
            $this->addSql(
                'UPDATE `user` SET `user_preferences`=JSON_SET(`user_preferences`, "$.locale", ?, "$.ipAddress", ?, "$.referrer", ?, "$.originUrl", ?) WHERE `user_id` = ?',
                [
                    $memberDetail['registration']['locale'],
                    $memberDetail['registration']['ip'],
                    $memberDetail['registration']['referrer_url'],
                    $memberDetail['registration']['referrer_origin_url'],
                    $member['customer_user_id']
                ]
            );
            $this->addSql(
                'UPDATE `customer` SET `customer_other_details` = JSON_REMOVE(`customer_other_details`, "$.registration") WHERE `customer_id` = ?',
                [$member['customer_id']]
            );
        }
    }
}
