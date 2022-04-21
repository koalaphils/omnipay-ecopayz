<?php

namespace DbBundle\Entity;

use DateTime;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use DbBundle\Entity\MemberRequest;
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
    const MEMBER_GENDER_NOT_SET = 0;
    const MEMBER_GENDER_MALE = 1;
    const MEMBER_GENDER_FEMALE = 2;

    public const DETAIL_BITCOIN = 'bitcoin';
    public const DETAIL_BITCOIN_INDEX = 'bitcoin.index';
    public const DETAIL_BITCOIN_ADDRESS = 'bitcoin.address';
    public const DETAIL_BITCOIN_CALLBACK = 'bitcoin.callback';
    public const DETAIL_BITCOIN_XPUB = 'bitcoin.xpub';
    protected $fName;
    protected $mName;
    protected $lName;
    private $fullName;
    protected $birthDate = null;
    protected $balance;
    protected $user;
    protected $socials;
    protected $transactionPassword;
    protected $level;
    protected $verifiedAt;
    protected $details;
    protected $joinedAt;
    protected $files;
    protected $contacts;
    protected $isAffiliate;
    protected $isCustomer;
    protected $affiliate;
    protected $currency;
    protected $country;
    private $groups;
    private $referrals;
    private $paymentOptions;
    private $products;
    private $transactions;
    private $product;
    private $extraPreferences;
    private $notifications;
    private $riskSetting;
    private $tags;
    private $referrerByCode;
    private $gender;
    private $allowRevenueShare;
    private $memberRequests;
    private $countryName;
    private $memberTags;
    private $locale;

    public function __construct()
    {
        $this->setVerifiedAt(null);
        $this->setUser(new User());
        $this->setBirthDate(null);
        $this->setGender(self::MEMBER_GENDER_NOT_SET);
        $this->setRevenueShare(false);
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
        $this->setGroups(new ArrayCollection([]));
        $this->setPaymentOptions(new ArrayCollection([]));
        $this->setProducts(new ArrayCollection([]));
        $this->memberTags = new ArrayCollection([]);
        $this->transactions = new ArrayCollection();
        $this->notifications = [];
        $this->setBalance(0);
        $this->tags = [];
        $this->locale = '';
    }

    /**
     * Set first name
     *
     * @param string $fname
     *
     * @return Customer
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

    public function setGender(int $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getGender(): int
    {
        return $this->gender;
    }

    /**
     * Set birthDate.
     *
     * @param DateTime $birthDate
     *
     * @return Customer
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
     * @return Customer
     */
    public function setBalance($balance = 0)
    {
        // zimi - 1544522191
        $this->balance = $this->balance + $balance;

        return $this;
    }

    /**
     * todo: DO NOT USE THIS! this is not used anymore
     * todo: what you would want to use is Customer::getAvailableBalance
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
     * @return User
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
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
     * @return Customer
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
     * @return Customer
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
     * @return Customer
     */
    public function setLevel($level = 1)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Set verified at.
     *
     * @param DateTime $verifiedAt
     *
     * @return Customer
     */
    public function setVerifiedAt($verifiedAt)
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
    }

    /**
     * Get verified at.
     *
     * @return DateTime
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

    public function getDetail($key, $default = null)
    {
        return array_get($this->getDetails(), $key, $default);
    }

    /**
     * Set details.
     *
     * @param array $details
     *
     * @return Customer
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

    public function hasDetail(string $key): bool
    {
        return array_has($this->details, $key);
    }

    /**
     * Get when the customer was join.
     *
     * @return DateTime
     */
    public function getJoinedAt()
    {
        return $this->joinedAt;
    }

    /**
     * Set when the customer was join.
     *
     * @param DateTime $joinedAt
     *
     * @return Customer
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
     * @return Customer
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
     * @return Customer
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
        return $this->getUser()->isAffiliate();
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

    public function setAffiliate(?int $affiliate)
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
     * @return Customer
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountryName(string $countryName): self
    {
        $this->countryName = $countryName;

        return $this;
    }

    public function getCountryName(): string
    {
        return $this->countryName ?? '';
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

    public function isProductActive(string $code) : bool
    {
        $allProducts = $this->products;
        $result = false;
        foreach($allProducts as $product) {
            if ($product->isActive() && ($product->getProduct()->getCode() === $code)) {
                return true;
            }
        }

        return $result;
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
        if ($this->extraPreferences) {
            $preferences += $this->extraPreferences;
        }

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

    public function getWebsocketChannel(): string
    {
        return $this->getDetail('websocket.channel_id', '');
    }

    public function setWebsocketChannel(string $channelId): self
    {
        $this->setDetail('websocket.channel_id', $channelId);

        return $this;
    }

    public function getReferrals()
    {
        return $this->referrals;
    }

    public function setReferrerByCode(Customer $referrer): void
    {
        $this->referrerByCode = $referrer;
    }

    public function getReferrerByCode(): ?Customer
    {
        return $this->referrerByCode;
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

    public function getReferrer()
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
        $this->setVerifiedAt(new DateTime());
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

    public function getMemberRequests(): PersistentCollection
    {
        return $this->memberRequests;
    }

    /**
     * @var string
     */
    private $pinUserCode;

    /**
     * @var string
     */
    private $pinLoginId;


    /**
     * Set pinUserCode
     *
     * @param string $pinUserCode
     *
     * @return Customer
     */
    public function setPinUserCode($pinUserCode)
    {
        $this->pinUserCode = $pinUserCode;

        return $this;
    }

    /**
     * Get pinUserCode
     *
     * @return string
     */
    public function getPinUserCode()
    {
        return $this->pinUserCode;
    }

    /**
     * Set pinLoginId
     *
     * @param string $pinLoginId
     *
     * @return Customer
     */
    public function setPinLoginId($pinLoginId)
    {
        $this->pinLoginId = $pinLoginId;

        return $this;
    }

    /**
     * Get pinLoginId
     *
     * @return string
     */
    public function getPinLoginId()
    {
        return $this->pinLoginId;
    }

    /**
     * Add referral
     *
     * @param Customer $referral
     *
     * @return Customer
     */
    public function addReferral(Customer $referral)
    {
        $this->referrals[] = $referral;

        return $this;
    }

    /**
     * Remove referral
     *
     * @param Customer $referral
     */
    public function removeReferral(Customer $referral)
    {
        $this->referrals->removeElement($referral);
    }
    /**
     * @var string
     */
    private $phoneNumber;


    /**
     * Set phoneNumber
     *
     * @param string $phoneNumber
     *
     * @return Customer
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get phoneNumber
     *
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function getReferrerCode(): ?string
    {
        return $this->getUser()->getPreference('affiliateCode');
    }

    public function linkReferrer(Customer $referrer): void
    {
        $this->setReferrer($referrer);
    }

    public function getCurrentReferrerDetails(): array
    {
        $referrer = $this->getAffiliate();
        $referrerDetails = [];

        return $referrerDetails;
    }

    public function getReferrerByCodeDetails(): array
    {
        $referrerByCode = $this->getReferrerByCode();
        $referrerDetails = [];

        if (!is_null($referrerByCode)) {
            $referrerDetails = [
                'id' => $referrerByCode->getId(),
                'fullName' => $referrerByCode->getFullName(),
                'username' => $referrerByCode->getUsername(),
            ];
        }

        return $referrerDetails;
    }

    public function setBitcoinDetails(array $details): self
    {
        $this->setDetail(self::DETAIL_BITCOIN, $details);

        return $this;
    }

    public function getBitcoinDetails(): ?array
    {
        return $this->getDetail(self::DETAIL_BITCOIN, null);
    }

    public function hasBitcoinReceivingAddress(): bool
    {
        return $this->hasDetail(self::DETAIL_BITCOIN_ADDRESS);
    }

    public function getBitcoinAddress(): string
    {
        return $this->getDetail(self::DETAIL_BITCOIN_ADDRESS);
    }

    public function hasBitcoinAddress(): bool
    {
        if (!$this->hasDetail(self::DETAIL_BITCOIN_ADDRESS)) {
            return false;
        }

        return $this->getBitcoinAddress() !== '';
    }

    public function getBitcoinIndex(): int
    {
        return (int) $this->getDetail(self::DETAIL_BITCOIN_INDEX);
    }

    public function getBitcoinCallback(): string
    {
        return $this->getDetail(self::DETAIL_BITCOIN_CALLBACK);
    }

    public function hasBitcoinCallback(): bool
    {
        return $this->hasDetail(self::DETAIL_BITCOIN_CALLBACK);
    }

    public function equalsToBitcoinCallback(string $callback): bool
    {
        if (!$this->hasBitcoinCallback()) {
            return false;
        }

        if(preg_match('/callback\/(.+)$/', $this->getBitcoinCallback(), $mathes) === 1 && preg_match('/callback\/(.+)$/', $callback, $paramMatches) === 1) {
            return current($paramMatches[1]) == current($mathes[1]);
        }

        return false;
    }

    public function bitcoinAddressBelongsToXpub(string $xpub): bool
    {
        return strcasecmp($this->getDetail(self::DETAIL_BITCOIN_XPUB), $xpub) === 0;
    }

    public function getLocale(): ?string
    {
        if ($this->locale === null || $this->locale === '') {
            if ($this->hasDetail('registration.locale')) {
                return $this->getDetail('registration.locale');
            }

            return 'en';
        }

        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function setRevenueShare(bool $revenueShare): self
    {
        $this->allowRevenueShare = $revenueShare;

        return $this;
    }

    public function isRevenueShareEnabled(): bool
    {
        return $this->allowRevenueShare;
    }
    
    public function isLinkedToAffiliate(): bool
    {
        return $this->getAffiliate() !== null;
    }

    public function getReferralCode(): ?string
    {
        return $this->getDetail('referral_code', null);
    }

    public function setPromoCode(string $key, ?string $value): void 
    {
        $promoCodes = $this->getDetail('promo_code');
        $promoCodes[$key] = $value;

        $this->setDetail('promo_code', $promoCodes);
    }

    public function getPromoCode(string $key): ?string
    {
        $codes = $this->getDetail('promo_code', ['custom' => null, 'refer_a_friend' => null]);

        return $codes[$key];
    }

    public function getPersonalLink(): ?string
    {
        return $this->getDetail('personal_link_id', null);
    }

    public function setPersonalLink(): self
    {
        $this->setDetail('personal_link_id', 'piw' . $this->getId());

        return $this;
    }

    public function removePersonalLink(): self
    {
        $this->setDetail('personal_link_id', null);

        return $this;
    }

    public function getHasPersonalLinkEnabled(): bool
    {
        if ($this->getCountry() == 'FR' && $this->getCurrency()->getCode() == 'EUR' && $this->getIsCustomer()) {
            return true;
        }

        return false;
    }

    public function getHasReferrerLinkEnabled(): bool
    {
        if ($this->getPromoCode('refer_a_friend') === 'REFERAFRIEND') {
            return true;
        }

        return false;
    }

    public function getMemberTags()
    {
        return $this->memberTags ?? new ArrayCollection([]);
    }

    public function setMemberTags(ArrayCollection $memberTags)
    {
        $memberTags = $memberTags ?? new ArrayCollection([]);

        if (!is_null($this->memberTags)) {
            foreach ($this->memberTags as $memberTag) {
                if (!$memberTags->contains($memberTag)) {
                    $this->removeMemberTags($memberTag);
                }
            }
        }

        if ($this->memberTags === null) {
            $this->memberTags = new ArrayCollection();
        }

        foreach ($memberTags as $memberTag) {
            if (!$this->memberTags->contains($memberTag)) {
                $this->addMemberTags($memberTag);
            }
        }

        return $this;
    }

    public function hasMemberTags(): bool
    {
        return (bool) !$this->getMemberTags()->isEmpty();
    }

    public function addMemberTags(MemberTag $memberTags): Customer
    {
        $this->memberTags = $this->memberTags ?? new ArrayCollection([]);

        if ($this->memberTags->contains($memberTags)) {
            return $this;
        }

        $this->memberTags->add($memberTags);
        $memberTags->addMember($this);
        if ($memberTags->getName() == 'Level 2') {
            $this->unverify();
        }

        return $this;
    }

    public function removeMemberTags(MemberTag $memberTags): Customer
    {
        $this->memberTags = $this->memberTags ?? new ArrayCollection([]);
        if (!$this->memberTags->contains($memberTags)) {
            return $this;
        }

        $this->memberTags->removeElement($memberTags);
        $memberTags->removeMember($this);

        if ($memberTags->getName() == 'Level 2') {
            $this->setVerifiedAt($this->getLevel1VerificationDate());
            $this->unverifyLevel2Verification();
        }

        return $this;
    }

    public function unverifyLevel2Verification(): void
    {
        $kyc =  $this->getDetail('kyc', null);
        $kyc['level_2'] = "UNVERIFIED";
        $kyc['level_2_verified_at'] = null;
        $this->setDetail('kyc', $kyc);
    }

    public function getLevel1VerificationDate(): ?\DateTime
    {
        $kyc =  $this->getDetail('kyc', null);

        return $kyc['level_1_verified_at']  ? new \DateTime($kyc['level_1_verified_at']) : null;
    }
}
