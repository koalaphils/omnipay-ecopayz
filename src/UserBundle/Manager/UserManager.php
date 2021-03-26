<?php

namespace UserBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\User;
use DbBundle\Repository\UserRepository;
use Firebase\JWT\JWT;

class UserManager extends AbstractManager
{
    private $tempPassword;

    public function __construct(string $tempPassword)
    {
        $this->tempPassword = $tempPassword;
    }

    /**
     * @return \DbBundle\Repository\UserRepository
     */
    public function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:User');
    }

    public function getAdminList($filters = null)
    {
        $results = [];

        if (array_get($filters, 'datatable', 0)) {

            $order = (!array_has($filters, 'order')) ? [['column' => 'u.createdAt', 'dir' => 'desc']] : $filters['order'];

            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }

            $filters['types'] = [User::USER_TYPE_ADMIN];
            $results['data'] = $this->getRepository()->getUserList($filters, $order);
            if (array_get($filters, 'route', 0)) {
                $results['data'] = array_map(function ($data) {
                    $data['user'] = $data;
                    $data['routes'] = [
                        'update' => $this->getRouter()->generate('user.update_page', ['id' => $data['user']['id']]),
                        'view' => $this->getRouter()->generate('user.view_page', ['id' => $data['user']['id']]),
                    ];

                    return $data;
                }, $results['data']);
            }
            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getUserListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getAdminCount();
        } elseif (array_get($filters, 'select2', 0)) {
            $filters['notNull'] = true;
            $results['items'] = array_map(
                function ($group) use ($filters) {
                    return [
                        'id' => $group[array_get($filters, 'zendeskIdColumn', 'zendeskId')],
                        'text' => $group['username'],
                    ];
                },
                $this->getRepository()->getUserList($filters)
            );
            $results['recordsFiltered'] = $this->getRepository()->getUserListFilterCount($filters);
        } else {
            $results = $this->getRepository()->getUserList($filters);
        }

        return $results;
    }

    public function getUserTypes(): array
    {
        $result[0] = [
            'id' => User::USER_TYPE_MEMBER,
            'name' => User::USER_TYPE_NAME_MEMBER
        ];

        $result[1] = [
            'id' => User::USER_TYPE_AFFILIATE,
            'name' => User::USER_TYPE_NAME_AFFILIATE
        ];

        return $result;
    }

    public function checkSuperAdmin($currRoles, $selectedRoles)
    {
        $status = true;
        if (in_array('role.admin', $currRoles)) {
            if (in_array('role.super.admin', $selectedRoles)) {
                $status = false;
            }
        }

        return $status;
    }

    /**
     * @param User   $user
     * @param string $channel
     * @param int    $num
     *
     * @return mixed
     */
    public function updateCounter(User $user, $channel, $num)
    {
        return $this->getRepository()->updateCounter($user, $channel, $num);
    }

    public function encodeActivationCode(User $user)
    {
        $encoder = $this->getEncoderFactory()->getEncoder(User::class);

        return $encoder->encodePassword(
            $this->generateActivationCode($user),
            User::getActivationCodeSalt()
        );
    }

    public function generateActivationCode(User $user)
    {
        return $user->getId() . '_' . generate_code(16, false, 'lud');
    }

    public function sendActivationMail(array $params)
    {
        $originFrom = $params['originFrom'];

        if ($this->wasRegisteredFromAsianOdds($originFrom)) {
            $params['originFrom'] =  $this->getParameter('asianconnect_url');
        }

        $this->getMailer()
            ->send(
                $this->getTranslator()->trans('email.subject.activation', [], "AppBundle"),
                $params['email'],
                'activation.html.twig',
                $params
            );
    }

    public function encodeResetPasswordCode(User $user)
    {
        $encoder = $this->getEncoderFactory()->getEncoder(User::class);

        return $encoder->encodePassword(
            $this->generateResetPasswordCode($user),
            User::getResetPasswordCodeSalt()
        );
    }

    public function generateResetPasswordCode(User $user)
    {
        return $user->getId() . '_' . generate_code(16, false, 'lud');
    }

    public function sendResetPasswordEmail(array $params)
    {
        $this->getMailer()
            ->send(
                $this->getTranslator()->trans('email.subject.resetPassword', [], "AppBundle"),
                $params['email'],
                'resetPassword.html.twig',
                $params
            );
    }

    public function encodePassword(User $user, $password)
    {
        $encoder = $this->getEncoderFactory()->getEncoder(User::class);

        return $encoder->encodePassword(
            $password,
            $user->getSalt()
        );
    }

    public function savePreferences(string $key, $value)
    {
        /* @var $user \DbBundle\Entity\User */
        $user = $this->getUser();
        $user->setPreference($key, $value);

        $this->save($user);
    }

    public function removePreferences(string $key)
    {
        /* @var $user \DbBundle\Entity\User */
        $user = $this->getUser();
        $user->removePreferences($key);

        $this->save($user);
    }

    public function sendResetPasswordLink(User $user, string $origin)
    {
        $user->setResetPasswordCode($this->encodeResetPasswordCode($user));
        $user->setResetPasswordSentTimestamp(new \DateTimeImmutable('now'));
        $user->setPassword($this->encodePassword($user, $this->tempPassword));
        $this->save($user);

        $resetPasswordCode = [
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'password' => $this->tempPassword,
            'resetPasswordCode' => $user->getResetPasswordCode(),
        ];

        $this->sendResetPasswordEmail([
            'email' => $user->getEmail(),
            'originFrom' => $origin,
            'resetPasswordCode' => JWT::encode($resetPasswordCode, 'AMSV2'),
        ]);
    }

    protected function getTranslator(): \Symfony\Component\Translation\TranslatorInterface
    {
        return $this->getContainer()->get('translator');
    }

    private function getEncoderFactory(): \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface
    {
        return $this->getContainer()->get('security.encoder_factory');
    }

    private function getMailer(): \AppBundle\Manager\MailerManager
    {
        return $this->getContainer()->get('app.mailer_manager');
    }

    private function getMediaManager(): \MediaBundle\Manager\MediaManager
    {
        return $this->getContainer()->get('media.manager');
    }

    private function wasRegisteredFromAsianOdds($originUrl) : Bool
    {
        $asianoddsDomain = $this->getParameter('asianodds_domain');
        $originDomain = parse_url($originUrl, PHP_URL_HOST);

        return $originDomain === $asianoddsDomain ? true : false;
    }
}
