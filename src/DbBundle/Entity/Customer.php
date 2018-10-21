<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use AppBundle\ValueObject\Number;

class Customer extends Entity implements AuditInterface, AuditAssociationInterface
{
    const AFFILIATE = 'Affiliate';
    const CUSTOMER = 'Customer';
    const MEMBER = 'Member';
    const ACRONYM_AFFILIATE = 'AFF';
    const ACRONYM_MEMBER = 'MBR';
    const CUSTOMER_ENABLED = 'enabled';
    const CUSTOMER_REGISTERED = 'registered';
    const CUSTOMER_SUSPENDED = 'suspended';

    /**
     * @var string
     */
    protected $fName;

    /**
     * @var string
     */
    protected $mName;

    /**
     * @var string
     */
    protected $lName;

    private $fullName;

    /**
     * @var date
     */
    protected $birthDate = null;

    /**
     * @var decimal
     */
    protected $balance;

    /**
     * @var \DbBundle\Entity\User
     */
    protected $user;

    /**
     * @var json
     */
    protected $socials;

    /**
     * @var string
     */
    protected $transactionPassword;

    /**
     * @var int
     */
    protected $level;

    /**
     * @var \DateTime
     */
    protected $verifiedAt;

    /**
     * @var array
     */
    protected $details;

    /**
     * @var \DateTime
     */
    protected $joinedAt;

    /**
     * @var array
     */
    protected $files;

    /**
     * @var array
     */
    protected $contacts;

    /**
     * @var type
     */
    protected $isAffiliate;

    /**
     * @var type
     */
    protected $isCustomer;

    /**
     * @var \DbBundle\Entity\Customer
     */
    protected $affiliate;

    /**
     * @var Currency
     */
    protected $currency;

    /**
     * @var Country
     */
    protected $country;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $groups;

    private $referrals;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $paymentOptions;

    /**
    * @var \Doctrine\Common\Collections\ArrayCollection
    */
    private $products;

    private $transactions;

    /**
     * @var string
     */
    private $product;

    private $extraPreferences;

    private $notifications;

    private $riskSetting;

    private $tags;

    public function __construct()
    {
        $this->setVerifiedAt(null);
        $this->setUser(new User());
        $this->setBirthDate(null);
        $this->setTransactionPassword('');
        $this->setLevel(1);
        $this->setMName('');
        $this->setSocials([]);
        $this->setDetails([]);
        $this->setContacts([]);
        $this->setFiles([]);
        $this->setIsCustomer(false);
        $this->setIsAffiliate(false);
        $this->setAffiliate(null);
        $this->setGroups(new \Doctrine\Common\Collections\ArrayCollection([]));
        $this->setPaymentOptions(new \Doctrine\Common\Collections\ArrayCollection([]));
        $this->setProducts(new \Doctrine\Common\Collections\ArrayCollection([]));
        $this->transactions = new ArrayCollection();
        $this->notifications = [];
        $this->setBalance(0);
        $this->tags = [];
    }

    /**
     * Set first name
     *
     * @param string $fname
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setFName($fname)
    {
        $this->fName = $fname;

        return $this;
    }

    /**
     * Get fName.
     *
     * @return string
     */
    public function getFName()
    {
        return $this->fName;
    }

    /**
     * Set middle name.
     *
     * @param string $mName
     *
     * @return string
     */
    public function setMName($mName = '')
    {
        $this->mName = $mName;

        return $this;
    }

    /**
     * Get mName.
     *
     * @return string
     */
    public function getMName()
    {
        return $this->mName;
    }

    /**
     * Set last name.
     *
     * @param string $lName
     *
     * @return string
     */
    public function setLName($lName)
    {
        $this->lName = $lName;

        return $this;
    }

    /**
     * Get lName.
     *
     * @return string
     */
    public function getLName()
    {
        return $this->lName;
    }

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Set birthDate.
     *
     * @param \DateTime $birthDate
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setBirthDate($birthDate = null)
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * Get birthDate.
     *
     * @return date
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * Set balance.
     *
     * @param double $balance
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setBalance($balance = 0)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance.
     *
     * @return decimal
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Set user.
     *
     * @param int $user
     *
     * @return \DbBundle\Entity\User
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \DbBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set socials.
     *
     * @param array $socials
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setSocials($socials)
    {
        $this->socials = $socials;

        return $this;
    }

    /**
     * Get socials.
     *
     * @return json
     */
    public function getSocials()
    {
        return $this->socials;
    }

    /**
     * Get the salt.
     *
     * @return string
     */
    public function getSalt()
    {
        return 'supermoonBetTransactionPassword';
    }

    /**
     * Get the transaction password.
     *
     * @return string
     */
    public function getTransactionPassword()
    {
        return $this->transactionPassword;
    }

    /**
     * Set the transaction password.
     *
     * @param string $password
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setTransactionPassword($password = '')
    {
        $this->transactionPassword = $password;

        return $this;
    }

    /**
     * Get level.
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set level.
     *
     * @param int $level
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setLevel($level = 1)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Set verified at.
     *
     * @param \DateTime $verifiedAt
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setVerifiedAt($verifiedAt)
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
    }

    /**
     * Get verified at.
     *
     * @return \DateTime
     */
    public function getVerifiedAt()
    {
        return $this->verifiedAt;
    }

    /**
     * Check if user is verified.
     *
     * @return bool
     */
    public function isVerified()
    {
        return !is_null($this->verifiedAt);
    }

    /**
     * Get details.
     *
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    public function getDetail($key)
    {
        return array_get($this->getDetails(), $key);
    }

    /**
     * Set details.
     *
     * @param array $details
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    public function setDetail(string $key, $detail): self
    {
        array_set($this->details, $key, $detail);

        return $this;
    }

    /**
     * Get when the customer was join.
     *
     * @return \DateTime
     */
    public function getJoinedAt()
    {
        return $this->joinedAt;
    }

    /**
     * Set when the customer was join.
     *
     * @param \DateTime $joinedAt
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setJoinedAt($joinedAt)
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    /**
     * Get all files associated to customer.
     *
     * @return array
     */
    public function getFiles(): array
    {
        if ($this->files === null) {
            $this->files = [];
        }

        return $this->files;
    }

    public function getFile($index)
    {
        return $this->files[$index];
    }

    /**
     * Associate files to customer.
     *
     * @param type $files
     *
     * @return $this
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @param array $file
     *
     * @return \DbBundle\Entity\Customer
     */
    public function addFile($file)
    {
        $this->files[] = $file;

        return $this;
    }

    public function updateFile($index, $info, $value)
    {
        $this->files[$index][$info] = $value;

        return $this;
    }

    public function deleteFile(string $fileName): void
    {
        foreach ($this->files as $index => $file) {
            if ($file['file'] === $fileName) {
                $this->removeFile($index);
                break;
            }
        }
    }

    public function removeFile($index)
    {
        unset($this->files[$index]);

        return $this;
    }

    /**
     * Get all contact information of the customer.
     *
     * @return array
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Set contact informations of the customer.
     *
     * @param array $contacts
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setContacts($contacts)
    {
        $this->contacts = $contacts;

        return $this;
    }

    public function setPaymentOptions($paymentOptions)
    {
        $this->paymentOptions = $paymentOptions;

        return $this;
    }

    public function getPaymentOptions()
    {
        return $this->paymentOptions;
    }

    public function addPaymentOption($paymentOption)
    {
        $this->paymentOptions->add($paymentOption);

        return $this;
    }

    public function removePaymentOption($index)
    {
        $this->paymentOptions->remove($index);

        return $this;
    }

    public function setPaymentOption($index, $paymentOption)
    {
        $this->paymentOptions->set($index, $paymentOption);

        return $this;
    }

    public function getPaymentOption($index)
    {
        return $this->paymentOptions->get($index);
    }

    public function setIsAffiliate($isAffiliate)
    {
        $this->isAffiliate = $isAffiliate;

        return $this;
    }

    public function getIsAffiliate()
    {
        return $this->isAffiliate;
    }

    public function setIsCustomer($isCustomer)
    {
        $this->isCustomer = $isCustomer;

        return $this;
    }

    public function getIsCustomer()
    {
        return $this->isCustomer;
    }

    public function setAffiliate($affiliate)
    {
        $this->affiliate = $affiliate;

        return $this;
    }

    public function getAffiliate()
    {
        return $this->affiliate;
    }

    /**
     * Set currency.
     *
     * @param string $currency
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return \DbBundle\Entity\Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set country.
     *
     * @param string $country
     *
     * @return \DbBundle\Entity\Customer
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return \DbBundle\Entity\Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return bool whether Customer belongs to a group or not
     */
    public function hasGroups() : bool
    {
        return (bool) !$this->getGroups()->isEmpty();
    }

    public function setGroups($groups)
    {
        $this->groups = $groups;

        return $this;
    }

    public function addGroup(CustomerGroup $customerGroup): Customer
    {
        if ($this->groups->contains($customerGroup)) {
            return $this;
        }

        $this->groups->add($customerGroup);
        $customerGroup->addCustomer($this);

        return $this;
    }

    public function removeGroup(CustomerGroup $customerGroup): Customer
    {
        if (!$this->groups->contains($customerGroup)) {
            return $this;
        }

        $this->groups->removeElement($customerGroup);
        $customerGroup->removeCustomer($this);

        return $this;
    }

    public function getProducts() 
    {
        return $this->products; 
    }

    /**
     * return Array of Active /DbBundle/Entity/Product
     */
    public function getActiveProducts() : array
    {
        $allProducts = $this->products;
        $activeProducts = [];
        foreach($allProducts as $product) {
            if ($product->isActive()) {
                $activeProducts[] = $product;
            }
        }

        return $activeProducts;
    }

    public function setProducts($products): self
    {
        $this->products = $products;

        return $this;
    }

    public function addProduct($product): self
    {
        $product->setCustomer($this);
        $this->products->add($product);

        return $this;
    }

    public function removeProduct($products): self
    {
        $this->products->remove($products);

        return $this;
    }

    public function addTransaction(Transaction $transaction): self
    {
        $this->transactions->add($transaction);

        return $this;
    }

    public function removeTransaction(Transaction $transaction): void
    {
        $this->transactions->removeElement($transaction);
    }

    public function getTransactions()
    {
        return $this->transactions;
    }

    public function getCustomerProductNames(): array
    {
        $products = [];

        foreach ($this->getProducts() as $cp) {
            $products[] = array(
                'productName' => $cp->getProduct()->getName(),
                'username' => $cp->getUserName()
            );
        }

        return $products;
    }

    public function getAvailableBalance(): float
    {
        $balance = new Number(0);
        foreach ($this->getProducts() as $product) {
            $balance = $balance->plus($product->getBalance());
        }

        return $balance->toFloat();
    }
    public function setExtraPreferences($extra): self
    {
        $this->extraPreferences = $extra;

        return $this;
    }

    public function getPreferences()
    {
        $preferences = $this->getUser()->getPreferences();
        $preferences += $this->extraPreferences;
        $preferences['paymentOptionTypes'] = [];

        foreach ($this->getPaymentOptions() as $paymentOption) {
            if (!in_array($paymentOption->getPaymentOption()->getCode(), $preferences['paymentOptionTypes'])) {
                $preferences['paymentOptionTypes'][] = $paymentOption->getPaymentOption()->getCode();
            }
        }

        return $preferences;
    }

    public function setEnabled(): void
    {
        $this->setDetail(self::CUSTOMER_ENABLED, true);
    }

    /**
     * Enabled is set to true whenever a user
     * has approved status on one of his transactions.
     */
    public function isEnabled(): bool
    {
        return $this->getDetail(self::CUSTOMER_ENABLED) ? true : false;
    }

    public function isActive() : bool
    {
        return $this->getUser()->isActive();
    }

    /**
     * prevent customer from logging in/using our service/etc
     */
    public function suspend()
    {
        $this->getUser()->setIsActive(false);
        return $this;
    }

    /**
     * enable customer to log-in and use our service/etc
     */
    public function activate()
    {
        $this->getUser()->setIsActive(true);
        return $this;
    }

    public function getWebsocketDetails(): ?array
    {
        return $this->getDetail('websocket');
    }

    public function getReferrals()
    {
        return $this->referrals;
    }

    /**
     * to get brokerage sync id
     */
    public function getBrokerageProductSyncId(): ?int
    {
        if (is_iterable($this->products)) {
            foreach ($this->products as $product) {
                if ($product->getDetail('brokerage')['sync_id'] && $product->getIsActive()) {
                    return $product->getDetail('brokerage')['sync_id'];
                }
            }
        }

        return 0;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_CUSTOMER;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'user', 'details', 'files', 'groups'];
    }

    public function getAssociationFields()
    {
        return ['group', 'affiliate', 'country', 'currency'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return $this->getFullName();
    }

    public function isAudit()
    {
        return true;
    }

    public function getAssociationFieldName()
    {
        return $this->getFullName();
    }

    public function setNotifications(array $notifications): self
    {
        $this->notifications = $notifications;

        return $this;
    }

    public function getNotifications(): array
    {
        return $this->notifications ? array_reverse($this->notifications) : [];
    }

    public function addNotification($notification): self
    {
        if ($this->notifications === null) {
            $this->notifications = [];
        }

        if (count($this->notifications) === 10) {
            array_shift($this->notifications);
        }

        $this->notifications[] = $notification;

        return $this;
    }

    public function readNotifications(): void
    {
        if ($this->notifications === null) {
            return;
        }

        foreach ($this->notifications as &$notif) {
            $notif['read'] = true;
        }
    }

    public function getAuditDetails(): array
    {
        return ['fName' => $this->getFName(), 'lName' => $this->getLName(), 'mName' => $this->getMName(), 'user' => $this->getUser(), 'riskSetting' => $this->getRiskSetting()];
    }

    public function unlinkReferral(): void
    {
        $this->setAffiliate(null);
    }

    public function hasReferral(): bool
    {
        return $this->getAffiliate() instanceof Customer;
    }

    public function getReferral(): ?Customer
    {
        return $this->getAffiliate();
    }

    public function setReferrer(Customer $customer): void
    {
        $this->setAffiliate($customer);
    }

    public function hasReferrer(): bool
    {
        return $this->getAffiliate() instanceof Customer;
    }

    public function getReferrer(): ?Customer
    {
        return $this->getAffiliate();
    }

    public function setRiskSetting(?string $riskSetting): self
    {
        $this->riskSetting = $riskSetting;

        return $this;
    }

    public function getRiskSetting(): ?string
    {
        return $this->riskSetting;
    }

    public function verify(): void
    {
        $this->setVerifiedAt(new \DateTime());
    }

    public function unverify(): void
    {
        $this->setVerifiedAt(null);
    }

    public function setTags(?array $tags): void
    {
        $this->tags = array_values($tags);
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function isTagAsAffiliate(): bool
    {
        return in_array(self::ACRONYM_AFFILIATE, $this->tags);
    }

    public function hasConfirmReferrerTermsAndConditions(): bool
    {
        return $this->getDetail('has_confirm_terms_and_conditions') == true ? true : false;
    }

    public function confirmReferrerTermsAndConditions(): void
    {
        $this->setDetail('has_confirm_terms_and_conditions', true);
    }

    public function unconfirmReferrerTermsAndConditions(): void
    {
        $this->setDetail('has_confirm_terms_and_conditions', false);
    }

    public function getCurrencyCode(): string
    {
        return $this->getCurrency()->getCode();
    }

    public function getUsername(): string
    {
        return $this->getUser()->getUsername();
    }
}