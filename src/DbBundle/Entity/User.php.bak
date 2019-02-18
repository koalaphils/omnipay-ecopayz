<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\AuditInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;

class User extends Entity implements ActionInterface, TimestampInterface, AdvancedUserInterface, \Serializable, AuditInterface
{
    use Traits\ActionTrait;
    use Traits\SoftDeleteTrait;
    use Traits\TimestampTrait;

    const USER_TYPE_MEMBER = 1;
    const USER_TYPE_ADMIN = 2;
    const USER_TYPE_CASHPAYER = 3;

    const SALT_ACTIVATION_CODE = 'activationCode';
    const SALT_TRANSACTION_PASSWORD = 'transactionPassword';
    const SALT_RESET_PASSWORD_CODE = 'resetPasswordCode';

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $plainPassword;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $isActive;

    /**
     * @var int
     */
    private $type;

    /**
     * @var array
     */
    private $roles;

    /**
     * @var \DbBundle\Entity\UserGroup
     */
    private $group;

    /**
     * @var int
     */
    private $zendeskId = null;

    private $preferences;

    /**
     * @var string
     */
    private $activationCode;

    /**
     * @var datetime
     */
    private $activationSentTimestamp;

    /**
     * @var datetime
     */
    private $activationTimestamp;

    private $resetPasswordCode;

    private $resetPasswordSentTimestamp;

    /**
     * @var string
     */
    private $userKey;

    private $customer;
    private $creator;
    private $auditRevision;
    private $restoreId;

    public function __construct()
    {
        $this->roles = [];
        $this->preferences = [];
        $this->isActive = true;
    }

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return User
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set isActive.
     *
     * @param string $isActive
     *
     * @return User
     */
    public function setIsActive($isActive = true)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return string
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    public function isActive() : bool
    {
        return $this->getIsActive() == true;
    }

    public function activate()
    {
        $this->isActive = true;

        return $this;
    }

    public function suspend()
    {
        $this->isActive = false;

        return $this;
    }

    public function serialize()
    {
        return serialize([
            $this->id,
            $this->username,
            $this->email,
        ]);
    }

    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->email
        ) = unserialize($serialized);
    }

    public function eraseCredentials()
    {
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    public function getRoles()
    {
        $roles = [];
        $groupRoles = [];

        if ($this->getType() == self::USER_TYPE_ADMIN) {
            $roles[] = 'role.admin';
            $roles[] = 'ROLE_ADMIN';
        } elseif ($this->getType() == self::USER_TYPE_MEMBER) {
            $roles[] = 'role.api';
            $roles[] = 'role.customer';
            $roles[] = 'ROLE_API';
            $roles[] = 'ROLE_CUSTOMER';
        }

        if ($this->getGroup()) {
            $groupRoles = $this->getGroup()->getFlattenRoles();
        }

        if (is_array($this->roles)) {
            foreach ($this->roles as $key => $role) {
                if ($role == 2) {
                    $roles[] = str_replace('_', '.', strtolower($key));
                    $roles[] = $key;
                }
                if ($role == 1 && in_array($key, $groupRoles)) {
                    $roles[] = str_replace('_', '.', strtolower($key));
                    $roles[] = $key;
                }
            }
        }

        return $roles;
    }

    public function isSuperAdmin(): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $this->getRoles());
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function getRolesPlain()
    {
        return $this->roles;
    }

    public function setRolesPlain($roles)
    {
        return $this->setRoles($roles);
    }

    public function getSalt()
    {
        return null;
    }

    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setZendeskId($zendeskId)
    {
        $this->zendeskId = $zendeskId;

        return $this;
    }

    public function getZendeskId()
    {
        return $this->zendeskId;
    }

    public function setPreferences($preferences = [])
    {
        $this->preferences = $preferences;

        return $this;
    }

    public function setPreference($key, $value): self
    {
        array_set($this->preferences, $key, $value);

        return $this;
    }

    public function removePreferences(string $key): self
    {
        array_forget($this->preferences, $key);

        return $this;
    }

    public function getPreferences()
    {
        return $this->preferences;
    }

    public function getChannelId()
    {
        return $this->getId() . $this->getZendeskId();
    }

    public function getPreference($key, $default = null)
    {
        return array_get($this->preferences, $key, $default);
    }

    /**
     * Set activationCode
     *
     * @param string $activationCode
     *
     * @return User
     */
    public function setActivationCode($activationCode)
    {
        $this->activationCode = $activationCode;

        return $this;
    }

    /**
     * Get activationCode
     *
     * @return string
     */
    public function getActivationCode()
    {
        return $this->activationCode;
    }

    /**
     * Set activationSentTimestamp
     *
     * @param \DateTime $activationSentTimestamp
     *
     * @return User
     */
    public function setActivationSentTimestamp($activationSentTimestamp)
    {
        $this->activationSentTimestamp = $activationSentTimestamp;

        return $this;
    }

    /**
     * Get activationSentTimestamp
     *
     * @return \DateTime
     */
    public function getActivationSentTimestamp()
    {
        return $this->activationSentTimestamp;
    }

    /**
     * Set activationTimestamp
     *
     * @param \DateTime $activationTimestamp
     *
     * @return User
     */
    public function setActivationTimestamp($activationTimestamp)
    {
        $this->activationTimestamp = $activationTimestamp;

        return $this;
    }

    /**
     * Get activationTimestamp
     *
     * @return \DateTime
     */
    public function getActivationTimestamp()
    {
        return $this->activationTimestamp;
    }

    /**
     * @return string
     */
    public static function getActivationCodeSalt(): string
    {
        return self::SALT_ACTIVATION_CODE;
    }

    /**
     * Set userKey
     *
     * @param string $userKey
     *
     * @return User
     */
    public function setUserKey($userKey)
    {
        $this->userKey = $userKey;

        return $this;
    }

    /**
     * Get userKey
     *
     * @return string
     */
    public function getUserKey()
    {
        return $this->userKey;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer($customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function setResetPasswordCode($resetPasswordCode): self
    {
        $this->resetPasswordCode = $resetPasswordCode;

        return $this;
    }

    public function getResetPasswordCode()
    {
        return $this->resetPasswordCode;
    }

    public function setResetPasswordSentTimestamp($resetPasswordSentTimestamp): self
    {
        $this->resetPasswordSentTimestamp = $resetPasswordSentTimestamp;

        return $this;
    }

    public function getResetPasswordSentTimestamp()
    {
        return $this->resetPasswordSentTimestamp;
    }

    public function isActivated(): bool
    {
        return $this->getActivationTimestamp() ? true : false;
    }

    public static function getTransactionPasswordSalt(): string
    {
        return self::SALT_TRANSACTION_PASSWORD;
    }

    public function setAsAdmin()
    {
        $this->type = User::USER_TYPE_ADMIN;
    }

    public static function getResetPasswordCodeSalt(): string
    {
        return self::SALT_RESET_PASSWORD_CODE;
    }

    /**
     * this has been inherited from AdvancedUserInterface
     * THIS IS NOT YET IMPLEMENTED for AC66BO
     * @return bool whether user is allowed to login or not depending if the account is flagged as "expired" for some reason
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * this has been inherited from AdvancedUserInterface
     * THIS IS NOT YET IMPLEMENTED for AC66BO
     * @return bool whether user is allowed to login or not depending if the account is flagged as "locked" for some reason
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * this has been inherited from AdvancedUserInterface
     * THIS IS NOT YET IMPLEMENTED for AC66BO
     * @return bool whether user is allowed to login or not depending on the expiry of his username/password
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * @return bool whether user is allowed to login or not
     */
    public function isEnabled()
    {
        return $this->isActive == true;
    }

    public function getTypeText()
    {
        if (!isset(self::getTypesText()[$this->type]) && $this->isCustomer()) {
            return self::USER_TYPE_MEMBER;
        }

        return self::getTypesText()[$this->type];
    }

    public function isCustomer() : bool
    {
        return ($this->getCustomer() instanceof  Customer);
    }

    public static function getTypesText()
    {
        return [
            self::USER_TYPE_ADMIN => 'admin',
            self::USER_TYPE_MEMBER => 'member',
            self::USER_TYPE_CASHPAYER => 'cashpayer',
        ];
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(User $creator)
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_USER;
    }

    public function getIgnoreFields()
    {
        return [
            'createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'preferences', 'activationCode', 'activationSentTimestamp',
            'activationTimestamp', 'resetPasswordCode', 'resetPasswordSentTimestamp', 'zendeskId', 'userKey', 'creator',
            'customer', 'deletedAt',
        ];
    }

    public function getAssociationFields()
    {
        return ['group'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return $this->getUsername();
    }

    public function isAudit()
    {
        return true;
    }

    public function getAuditRevision()
    {
        return $this->auditRevision;
    }

    public function setRestoreId(string $restoreId): self
    {
        $this->setPreference('restore_id', $restoreId);

        return $this;
    }

    public function getRestoreId(): string
    {
        return $this->getPreference('restore_id');
    }

    public function getAuditDetails(): array
    {
        return [
            'username' => $this->getUsername(),
            'email' => $this->getEmail(),
            'type' => $this->getType(),
            'isActive' => $this->getIsActive(),
            'customer' => $this->getCustomer(),
        ];
    }

    public function getMember(): Customer
    {
        return $this->getCustomer();
    }

    public function getMemberId(): int
    {
        return $this->getMember()->getId();
    }
}
