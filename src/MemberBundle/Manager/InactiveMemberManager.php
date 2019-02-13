<?php

namespace MemberBundle\Manager;

use DbBundle\Entity\InactiveMember;
use Doctrine\ORM\EntityManager;
use DbBundle\Entity\Customer as Member;
use TransactionBundle\Manager\TransactionManager;

class InactiveMemberManager
{
    private $entityManager;
    private $transactionManager;

    public function __construct(EntityManager $entityManager, TransactionManager $transactionManager)
    {
        $this->entityManager = $entityManager;
        $this->transactionManager = $transactionManager;
    }

    /**
     * @return array [
     *             [
     *                'memberId' => int,
     *                'name' => string,
     *                'currencyCode' => string
     *                'dateJoined' => string
     *            ],
     *            ...
     * ]
     */
    public function getInactiveMembersForListing(int $itemsPerPage = 10,int $offset = null,?string $customerNameToSearch): array
    {
        $criteria = [];
        if (!empty($customerNameToSearch)) {
            $criteria = ['member' => $this->getMemberIdsByFullNameSearch($customerNameToSearch)];
        }

        $members = $this->entityManager->getRepository(InactiveMember::class)->findBy($criteria, null, $itemsPerPage, $offset);
        $memberData = [];
        foreach ($members as $member) {
            $lastTransactionDate = $this->transactionManager->findLastTransactionDateByMemberId($member->getMemberId());
            $memberData[] = [
                'memberId' => $member->getMemberId(),
                'name' => $member->getMemberFullname(),
                'currencyCode' => $member->getMemberCurrencyCode(),
                'dateJoined' => $member->getMemberJoinedDate()->format('F d, Y H:i:s A'),
                'listedAsInactiveAt' => $member->getDateAdded()->format('F d, Y H:i:s A'),
                'balance' => $member->getTotalProductBalance(),
                'lastTransactionDate' => (!empty($lastTransactionDate) ? $lastTransactionDate->format('F d, Y H:i:s A') : '-' ),
            ];

        }

        return $memberData;
    }

    public function getFilteredInactiveMembersCount(?string $customerNameToSearch): int
    {
        $criteria = [];
        if (!empty($customerNameToSearch)) {
            $criteria = ['member' => $this->getMemberIdsByFullNameSearch($customerNameToSearch)];
        }

        $members = $this->entityManager->getRepository(InactiveMember::class)->findBy($criteria);
        $count = count($members);

        return $count;
    }

    /**
     * @return array of MemberIds currently listed as "inactive
     */
    public function getInactiveMemberIds(): array
    {
        $members = $this->entityManager->getRepository(InactiveMember::class)->findAll();
        $memberIds = [];
        foreach ($members as $member) {
            $memberIds[] = $member->getMemberId();
        }

        return $memberIds;
    }

    private function getMemberIdsByFullNameSearch(string $memberFullname): array
    {
        $members = $this->entityManager->getRepository(Member::class)->findMembersByFullNamePartialMatch($memberFullname);
        $memberIds = [];
        foreach ($members as $member) {
            $memberIds[] = $member->getId();
        }

        return $memberIds;
    }

    public function getInactiveMembersCount(): int
    {
       return $this->entityManager->getRepository(InactiveMember::class)->getInactiveMembersCount();
    }

    /**
     *  this computes "live" data from Member and Transaction tables, expect this to be slow
     *  if you need the list of members currently flagged as "inactive" use self::getInactiveMemberIds() instead
     * @return array of $memberIds
     *
     *
     */
    public function getMembersWithNoActivityForPastSixMonths(): array
    {
        $memberIds = $this->entityManager->getRepository(InactiveMember::class)->findMembersWithNoActivityForPastSixMonths();

        return $memberIds;
    }

    public function updateInactiveList(): void
    {
        $this->entityManager->getRepository(InactiveMember::class)->clearList();
        $memberIds = $this->getMembersWithNoActivityForPastSixMonths();
        $runs = 0;
        $batchSize = 1000;
        foreach ($memberIds as $memberId) {
            $member = $this->entityManager->getReference(Member::class, $memberId);
            $inactiveMember = $this->createInactiveMember($member);
            $this->entityManager->persist($inactiveMember);

            if ($runs % $batchSize === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                gc_collect_cycles();
            }

            $runs++;
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function createInactiveMember(Member $member): InactiveMember
    {
        $inactiveMember = new InactiveMember();
        $inactiveMember->setMember($member);
        $inactiveMember->setDateAdded(new \DateTimeImmutable());

        return $inactiveMember;
    }

    public function removeMemberIdFromList(int $memberId): bool
    {
        $results = $this->entityManager->getRepository(InactiveMember::class)->findBy(['member' => $memberId], null, $limit = 1);
        $inactiveMember = $results[0];
        if ($inactiveMember instanceof  InactiveMember) {
            $this->entityManager->remove($inactiveMember);
            $this->entityManager->flush($inactiveMember);

            return true;
        }

        return false;
    }
}