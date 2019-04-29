<?php

namespace UserBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use UserBundle\Form\UserType;
use UserBundle\Form\SecurityType;
use DbBundle\Entity\User;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_USER_VIEW']);

        return $this->render('UserBundle:Default:index.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_USER_VIEW']);
        $filters = $request->request->all();
        $filters = array_merge($filters, $request->query->all());
        $results = $this->getManager()->getAdminList($filters);

        return new JsonResponse($results, JsonResponse::HTTP_OK);
    }

    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_USER_CREATE']);
        $form = $this->createForm(UserType::class, null, [
            'action' => $this->getRouter()->generate('user.save'),
            'validation_groups' => ['default', 'withPassword'],
        ]);
        $form->handleRequest($request);

        return $this->render('UserBundle:Default:create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function saveAction(Request $request, $id = 'new')
    {
        $validationGroups = ['default'];
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_USER_CREATE']);
            $user = new User();
            $user->setAsAdmin();
            $user->setSignupType(User::SIGNUP_TYPE_EMAIL);
        } else {
            $this->denyAccessUnlessGranted(['ROLE_USER_UPDATE']);
            $user = $this->getUserRepository()->find($id);
            $oldPassword = $user->getPassword();
        }
        if ('new' === $id || array_get($request->request->get('User'), 'changePassword', 0)) {
            $validationGroups[] = 'withPassword';
        }

        $form = $this->createForm(UserType::class, $user, [
            'validation_groups' => $validationGroups,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            if ('new' === $id || array_get($request->request->get('User'), 'changePassword', 0)) {
                $password = $this->get('security.password_encoder')
                    ->encodePassword($user, $user->getPassword());
                $user->setPassword($password);
            } else {
                $user->setPassword($oldPassword);
            }

            $this->getUserRepository()->save($user);

            $this->getSession()->getFlashBag()->add('notifications', [
                'title' => $this->getTranslator()->trans(
                    'notification.title',
                    ['%username%' => $user->getUsername()],
                    'UserBundle'
                ),
                'message' => $this->getTranslator()->trans(
                    'notification.' . ($id === 'new' ? 'created' : 'updated'),
                    ['%username%' => $user->getUsername()],
                    'UserBundle'
                ),
            ]);

            return $this->redirectToRoute('user.update_page', ['id' => $user->getId()]);
        }

        return $this->redirect($request->headers->get('referer'), 307);
    }

    public function viewAction(Request $request, $id)
    {
    }

    public function updateAction(Request $request, $id)
    {
        if ($this->getUser()->getId() == $id) {
            return $this->redirectToRoute('user.myaccount_page');
        }

        $this->denyAccessUnlessGranted(['ROLE_USER_UPDATE']);
        $this->getMenuManager()->setActive('user.list');
        $user = $this->getUserRepository()->find($id);

        if (true !== $this->getManager()->checkSuperAdmin($this->getUser()->getRoles(), $user->getRoles())) {
            //throw $this->createAccessDeniedException('Access Denied');
        }

        $validationGroups = ['default'];
        if ('new' === $id || array_get($request->request->get('User'), 'changePassword', 0)) {
            $validationGroups[] = 'withPassword';
        }
        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->getRouter()->generate('user.save', ['id' => $id]),
            'validation_groups' => $validationGroups,
        ]);
        $form->handleRequest($request);

        return $this->render('UserBundle:Default:update.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    public function savePasswordAction(Request $request, $id)
    {
        $user = $this->getUserRepository()->find($id);
        $validationGroups = ['default', 'withPassword'];
        $form = $this->createForm(SecurityType::class, $user, [
            'validation_groups' => $validationGroups,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            $this->getUserRepository()->save($user);

            $this->getSession()->getFlashBag()->add('notifications', [
                'type' => 'success',
                'title' => $this->getTranslator()->trans('notification.password.title', [], 'UserBundle'),
                'message' => $this->getTranslator()->trans('notification.password.message', [], 'UserBundle'),
            ]);
        }

        return $this->redirect($request->headers->get('referer'), 307);
    }

    public function myAccountAction(Request $request)
    {
        $user = $this->getUser();
        $validationGroups = ['default', 'withPassword'];
        $form = $this->createForm(SecurityType::class, $user, [
            'validation_groups' => $validationGroups,
            'action' => $this->getRouter()->generate('user.password_save', ['id' => $user->getId()]),
        ]);

        $form->handleRequest($request);

        return $this->render('UserBundle:Default:myAccount.html.twig', [
            'user' => $user,
            'securityForm' => $form->createView(),
        ]);
    }

    public function savePreferencesAction(Request $request, $userId = null)
    {
        /* @var $user \DbBundle\Entity\User */
        if ($userId === null) {
            $user = $this->getUser();
        } else {
            $user = $this->getUserRepository()->find($userId);
        }

        $preferences = $request->get('preferences');
        $flattenPreferences = array_dot($preferences);

        foreach ($flattenPreferences as $key => $value) {
            $user->setPreference($key, $value);
        }

        $this->getUserRepository()->save($user);

        return $this->json(['success' => true]);
    }

    /**
     * Get user repository.
     *
     * @return \DbBundle\Repository\UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:User');
    }

    /**
     * Get User Manager.
     *
     * @return \UserBundle\Manager\UserManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('user.manager');
    }

    private function preventDeactivationOfOwnAccount($userId)
    {
        if ($this->getUser()->getId() === $userId) {
            throw new \Exception('You cannot suspend your own account.');
        }
    }

    public function suspendAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_USER_UPDATE']);
        $userId = $request->request->get('userId');

        $this->preventDeactivationOfOwnAccount($userId);

        $repo = $this->getUserRepository();
        $user = $repo->find($userId);
        if (!$user) {
            throw new \Doctrine\ORM\NoResultException;
        } else if ($user->isActive()) {
            $user->suspend();
            $repo->save($user);

            $notificationData = [
                'type'      => 'success',
                'title'     => 'Activation',
                'message'   => 'User '. $user->getUsername() .' has been suspended',
            ];

            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $notificationData);
                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $notificationData, JsonResponse::HTTP_OK]);
            }
        } else {
            throw new \Exception('User is already suspended', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function activateAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_USER_UPDATE']);
        $id = $request->request->get('userId');
        $repo = $this->getUserRepository();
        $user = $repo->find($id);
        if (!$user) {
            throw new \Doctrine\ORM\NoResultException;
        } else if (!$user->isActive()) {
            $user->activate();
            $repo->save($user);

            $notificationData = [
                'type'      => 'success',
                'title'     => 'Activation',
                'message'   => 'User '. $user->getUsername() .' has been activated',
            ];

            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $notificationData);
                return $this->redirect($request->headers->get('referer'), Response::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $notificationData, JsonResponse::HTTP_OK]);
            }
        } else {
            throw new \Exception('User is already activated', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
