<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Traits\ActionTrait;
use DbBundle\Entity\Traits\SoftDeleteTrait;
use DbBundle\Entity\Traits\TimestampTrait;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\AuditRevisionLog;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use MemberRequestBundle\Model\MemberRequest\Kyc as KycModel;
use MemberRequestBundle\Model\MemberRequest\ProductPassword as ProductPasswordModel;

/**
 * MemberRequests.
 */
class MemberRequest extends Entity implements ActionInterface, TimestampInterface, AuditInterface
{
    use ActionTrait;
    use SoftDeleteTrait;
    use TimestampTrait;
 
    const MEMBER_REQUEST_TYPE_PRODUCT_PASSWORD = 1;
    const MEMBER_REQUEST_TYPE_KYC = 2;
    const MEMBER_REQUEST_TYPE_GAUTH = 3;
    
    const MEMBER_REQUEST_STATUS_START = 1;
    const MEMBER_REQUEST_STATUS_END = 2;
    const MEMBER_REQUEST_STATUS_DECLINE = 3;
    const MEMBER_REQUEST_STATUS_ACKNOWLEDGE = 4;

    const MEMBER_REQUEST_TYPE_TEXT_KYC = 'kyc';
    
    private $number;
    private $member;
    private $type;
    private $status;
    private $date;
    private $details;
    private $subRequests;
    

    public function __construct()
    {
        $this->type = 0;
        $this->setStatus(self::MEMBER_REQUEST_STATUS_START);
        $this->setDetails([]);
        $this->subRequests = [];
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setDate(?DateTime $date): self
    {
        $this->date = $date;
        
        return $this;
    }

    public function getDate(): ?DateTIme
    {
        return $this->date;
    }

    public function setMember(Member $member): self
    {
        $this->member = $member;

        return $this;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setDetails(array $details = []): self
    {
        $this->details = $details;

        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function setDetail(string $key, $value): self
    {
        array_set($this->details, $key, $value);

        return $this;
    }

    public function appendToDetail(string $key, array $array_values): self
    {
        $this->details = array_append($this->details, json_encode($array_values), $key);

        return $this;
    }

    public function getDetail(string $key, $default = null)
    {
        return array_get($this->details, $key, $default);
    }

    public function getIgnoreFields(): array
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
    }

    public function getAssociationFields(): array
    {
        return ['member'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return $this->getNumber();
    }

    public function isAudit()
    {
        return true;
    }

    public function getAuditDetails(): array
    {
        return [
            'number' => $this->getNumber(),
            'type' => $this->getType(),
            'date' => $this->getDate(),
            'status' => $this->getStatus(),
        ];
    }

    public function getCategory(): int
    {
        if ($this->isProductPassword()) {
             $category = AuditRevisionLog::CATEGORY_MEMBER_TRANSACTION_PRODUCT_PASSWORD;
        } elseif ($this->isKyc()) {
            $category = AuditRevisionLog::CATEGORY_MEMBER_REQUEST_KYC;
        } elseif ($this->isGoogleAuth()) {
            $category = AuditRevisionLog::CATEGORY_MEMBER_REQUEST_GAUTH;
        }

        return $category;
    }

    public function getTypeText(): string
    {
        return $this->getTypesText()[$this->getType()];
    }

    public function getTypesText(): array
    {
        return [
            self::MEMBER_REQUEST_TYPE_KYC => 'kyc',
            self::MEMBER_REQUEST_TYPE_GAUTH => 'google_auth',
            self::MEMBER_REQUEST_TYPE_PRODUCT_PASSWORD => 'product_password',
        ];
    }

    public function getCleanTypeText(): string
    {
        return $this->getTypesCleanText()[$this->getType()];
    }

    public function getTypesCleanText(): array
    {
        return [
            self::MEMBER_REQUEST_TYPE_KYC => 'KYC',
            self::MEMBER_REQUEST_TYPE_GAUTH => 'Reset Google Authentication',
            self::MEMBER_REQUEST_TYPE_PRODUCT_PASSWORD => 'Reset Product Password',
        ];
    }

    public function getStatusText(): string
    {
        return $this->getStatusesText()[$this->getStatus()];
    }

    public function getStatusesText(): array
    {
        return [
            self::MEMBER_REQUEST_STATUS_START => 'requested',
            self::MEMBER_REQUEST_STATUS_END => 'processed',
            self::MEMBER_REQUEST_STATUS_DECLINE => 'declined',
            self::MEMBER_REQUEST_STATUS_ACKNOWLEDGE => 'acknowledged',
        ];
    }

    public function getPendingStatus(): array
    {
        return [
            self::MEMBER_REQUEST_STATUS_START,
            self::MEMBER_REQUEST_STATUS_ACKNOWLEDGE,
        ];
    }

    public function getNonPendingStatus(): array
    {
        return [
            self::MEMBER_REQUEST_STATUS_END,
            self::MEMBER_REQUEST_STATUS_DECLINE
        ];
    }

    public function isKyc(): bool
    {
        return $this->getType() === self::MEMBER_REQUEST_TYPE_KYC;
    }

    public function isProductPassword(): bool
    {
        return $this->getType() === self::MEMBER_REQUEST_TYPE_PRODUCT_PASSWORD;
    }

    public function isGoogleAuth(): bool
    {
        return $this->getType() === self::MEMBER_REQUEST_TYPE_GAUTH;
    }

    public function isStart(): bool
    {
        return $this->getStatus() === self::MEMBER_REQUEST_STATUS_START;
    }

    public function getSubRequests(): array
    {
        return $this->getDetail('sub_requests', []);
    }

    public function isEnd(): bool
    {
        return $this->getStatus() === self::MEMBER_REQUEST_STATUS_END;
    }

    public function isDeclined(): bool
    {
        return $this->getStatus() === self::MEMBER_REQUEST_STATUS_DECLINE;
    }

    public function setSubRequests(array $subRequests = []): self
    {
        $newRequests = new ArrayCollection([]);
        foreach ($subRequests as $index => $subRequest) {
            $newRequests->add($subRequest);
        }
        $this->subRequests = $newRequests;

        return $this;
    }

    public function setKycSubRequests(array $subRequests): self
    {
        $newRequest = new ArrayCollection([]);
        if (empty($subRequests)) {
            return $this;
        }
        $i = 0;
        foreach ($subRequests as $subRequest) {
            $kycModel = new KycModel();
            $kycModel->setRemark($subRequest['remark'] ?? $this->getDetail('sub_requests.' . $i . '.remark', null));
            $kycModel->setFilename($subRequest['filename'] ?? $this->getDetail('sub_requests.' . $i . '.filename', ''));
            $kycModel->setRequestedAt($subRequest['requested_at'] ?? $this->getDetail('sub_requests.' . $i . '.requested_at', null));
            $kycModel->setStatus($subRequest['status'] ?? $this->getDetail('sub_requests.' . $i . '.status', null));
            $kycModel->setIsDeleted($subRequest['is_deleted'] ?? $this->getDetail('sub_requests.' . $i . '.is_deleted', false));
            $newRequest->add($kycModel);
            $i++;
        }
        $this->subRequests = $newRequest;

        return $this;
    }

    public function getKycSubRequests(): ArrayCollection
    {
        return $this->subRequests;
    }

    public function setProductPasswordSubRequests(array $subRequests = []): self
    {
        $newRequest = new ArrayCollection([]);
        $i = 0;
        foreach ($subRequests as $key => $subRequest) {
            $productPasswordModel = new ProductPasswordModel();
            $currentMemberProductId = $this->getDetail('sub_requests.' . $i . '.member_product_id', '');
            if ((int) array_get($subRequest, 'member_product_id', false) === $currentMemberProductId) {
                $productPasswordModel->setPassword($subRequest['password'] ?? $this->getDetail('sub_requests.' . $i . '.password', ''));
                $productPasswordModel->setMemberProductId($subRequest['member_product_id'] ?? $this->getDetail('sub_requests.' . $i . '.member_product_id', ''));
                $newRequest->add($productPasswordModel);
            }

            $i++;
        }

        $this->subRequests = $newRequest;

        return $this;
    }

    public function getProductPasswordSubRequests(): ?ArrayCollection
    {
        return $this->subRequests;
    }

    public function hasOnlyOneRequestToBeDeleted(): bool
    {
        $subRequests = $this->getSubRequests();
        $i = 0;
        foreach ($subRequests as $subRequest) {
            if (!$subRequest['is_deleted'] ?? false) {
                $i++;
            }
        }

        return $i <= 1;
    }

    public function hasOnlyOneRequestToBeValidated(): bool
    {
        $subRequests = $this->getSubRequests();
        $i = 0;
        foreach ($subRequests as $subRequest) {
            if (!$subRequest['status'] ?? false) {
                $i++;
            }
        }

        return $i <= 1;
    }

    public function hasOnlyOneRequestToBeInvalidated(): bool
    {
        $subRequests = $this->getSubRequests();
        $i = 0;
        foreach ($subRequests as $subRequest) {
            if ($subRequest['status'] !== 0) {
                $i++;
            }
        }

        return $i <= 1;
    }

    public function hasDocumentFilename($memberRequest, string $filename): ?int
    {
        $subRequests = $memberRequest->getSubRequests();
        $i = 0;
        foreach ($subRequests as $key => $subRequest) {
            if ($subRequest['filename']  == $filename) {
                return $i;
            }
            $i++;
        }

        return null;
    }

    public function setDocumentValidated(int $key, string $remark, int $status): self
    {
        $this->setDetail('sub_requests.'. $key .'.status', $status);
        $oldRemark = $this->getDetail('sub_requests.'. $key .'.remark', '');
        if ($oldRemark === '') {
            $this->setDetail('sub_requests.'. $key .'.remark', trim($remark));
        }

        return $this;
    }

    public function setDocumentAsValid(int $key): self
    {
        $this->setDetail('sub_requests.'. $key .'.status', 1);

        return $this;
    }

    public function deleteDocumentRecord(int $key): self
    {
        $this->setDetail('sub_requests.'. $key .'.is_deleted', true);
        $this->setDetail('sub_requests.'. $key .'.status', 0);

        return $this;
    }

    public function getRecordByIndex(int $key) : array
    {
        return $this->getDetail('sub_requests.' . $key, []);
    }

    public function isValidDocument(int $key): bool
    {
        return $this->getDetail('sub_requests.' . $key . '.status', null) === 1;
    }
    
    public function isInvalidDocument(int $key): bool
    {
        return $this->getDetail('sub_requests.' . $key . '.status', null) === 0;
    }

    public function isEmptyRemark(int $key): bool
    {
        return $this->getDetail('sub_requests.' . $key . '.remark', '') !== '';
    }

    public function isRemarkSet(int $key): bool
    {
        return $this->getDetail('sub_requests.' . $key . '.remark', null) !== null;
    }

    public function isRequesthestDeleted(int $key): bool
    {
        return $this->getDetail('sub_requests.' . $key . '.is_deleted', false);
    }

    public function isForProcessing(): bool
    {
        return !$this->getStatus() === self::MEMBER_REQUEST_STATUS_ACKNOWLEDGE;
    }

    public function isAcknowledged(): bool
    {
        return $this->getStatus() === self::MEMBER_REQUEST_STATUS_ACKNOWLEDGE;
    }

    public function getMemberProductAndUserName(int $memberProductId): string
    {
        
        foreach ($this->getMember()->getProducts() as $memberProduct) {
            if ($memberProductId == $memberProduct->getId()) {
                return $memberProduct->getProduct()->getName() . ' (' . $memberProduct->getUserName() . ')'; 
            }
        }
        
        return '';
    }

    public function isProductPasswordHadBeenAcknowledged(): bool
    {
        return $this->isProductPassword() && $this->getStatus() == self::MEMBER_REQUEST_STATUS_ACKNOWLEDGE;
    }

    public function hasPendingRequest(): bool
    {
        return $this->getStatus() === self::MEMBER_REQUEST_STATUS_START || $this->getStatus() === self::MEMBER_REQUEST_STATUS_ACKNOWLEDGE;
    }

    public function setNotes($value = ''): self
    {
        $this->setDetail('notes', $value);

        return $this;
    }
    
    public function setRequests(int $key, string $field, $value): self
    {
        $this->setDetail('sub_requests.'. $key .'.' . $field, $value);

        return $this;
    }

    public function isNew(): bool
    {
        return $this->id === null;
    }

    public function setProductPasswordEntry(int $key, $value): self
    {
        $this->setDetail('sub_requests.' . $key , $value);

        return $this;
    }

}
