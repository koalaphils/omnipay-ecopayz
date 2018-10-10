<?php

namespace GroupBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use GroupBundle\Form\UserGroupType;
use DbBundle\Entity\UserGroup;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_GROUP_VIEW']);

        return $this->render('GroupBundle:Default:index.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_GROUP_VIEW']);
        $filters = $request->request->all();
        $results = $this->getManager()->getGroupList($filters);

        return new JsonResponse($results, JsonResponse::HTTP_OK);
    }

    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_GROUP_CREATE']);
        $form = $this->createForm(UserGroupType::class, null, [
            'action' => $this->getRouter()->generate('group.save'),
        ]);
        $form->handleRequest($request);

        $roles = $this->getRoleManager()->getAllRoles();
        $roleGroups = $this->getRoleManager()->getGroups();

        return $this->render('GroupBundle:Default:create.html.twig', [
            'form' => $form->createView(),
            'roles' => $roles,
            'roleGroups' => $roleGroups,
        ]);
    }

    public function viewAction(Request $request, $id)
    {
    }

    public function updateAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_GROUP_UPDATE']);
        $this->getMenuManager()->setActive('group.list');

        $group = $this->getUserGroupRepository()->find($id);

        $form = $this->createForm(UserGroupType::class, $group, [
            'action' => $this->getRouter()->generate('group.save', ['id' => $id]),
        ]);
        $form->handleRequest($request);

        $roles = $this->getRoleManager()->getAllRoles();
        $roleGroups = $this->getRoleManager()->getGroups();

        return $this->render('GroupBundle:Default:update.html.twig', [
            'form' => $form->createView(),
            'group' => $group,
            'roles' => $roles,
            'roleGroups' => $roleGroups,
        ]);
    }

    public function saveAction(Request $request, $id = 'new')
    {
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_GROUP_CREATE']);
            $group = new UserGroup();
        } else {
            $this->denyAccessUnlessGranted(['ROLE_GROUP_UPDATE']);
            $group = $this->getUserGroupRepository()->find($id);
        }

        $form = $this->createForm(UserGroupType::class, $group);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group = $form->getData();

            $this->getUserGroupRepository()->save($group);

            $this->getSession()->getFlashBag()->add('notifications', [
                'title' => $this->getTranslator()->trans('notification.title', ['%name%' => $group->getName()], 'GroupBundle'),
                'message' => $this->getTranslator()->trans(
                    'notification.' . ($id === 'new' ? 'created' : 'updated'),
                    ['%name%' => $group->getName()],
                    'GroupBundle'
                ),
            ]);

            return $this->redirectToRoute('group.update_page', ['id' => $group->getId()]);
        }

        return $this->redirect($request->headers->get('referer'), 307);
    }

    /**
     * Get user repository.
     *
     * @return \DbBundle\Repository\UserGroupRepository
     */
    protected function getUserGroupRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:UserGroup');
    }

    protected function getManager()
    {
        return $this->getContainer()->get('group.manager');
    }

    private function getRoleManager(): \AppBundle\Manager\RoleManager
    {
        return $this->get('app.role_manager');
    }
}
