<?php

namespace NoticeBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use NoticeBundle\Form\NoticeType;
use DbBundle\Entity\Notice;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_NOTICE_VIEW']);
        $types = [];
        foreach (Notice::getTypes() as $key => $type) {
            $types[$type] = $this->getTranslator()->trans($key, [], 'NoticeBundle');
        }

        return $this->render('NoticeBundle:Default:index.html.twig', ['types' => $types]);
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_NOTICE_VIEW']);
        $filters = $request->request->all();
        $results = $this->getManager()->getNoticeList($filters);

        return new JsonResponse($results, JsonResponse::HTTP_OK);
    }

    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_NOTICE_CREATE']);
        $form = $this->createForm(NoticeType::class, null, [
            'action' => $this->getRouter()->generate('notice.save'),
        ]);
        $form->handleRequest($request);

        return $this->render('NoticeBundle:Default:create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function updateAction(Request $request, $id)
    {
        if (!$this->isGranted(['ROLE_NOTICE_UPDATE'])) {
            return $this->redirectToRoute('notice.view_page', ['id' => $id]);
        }
        $this->getMenuManager()->setActive('notice.list');

        $notice = $this->getNoticeRepository()->find($id);

        if (!$notice) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(sprintf('Notice not found for "%s %s"', $request->getRealMethod(), $request->getPathInfo()));
        }

        $form = $this->createForm(NoticeType::class, $notice, [
            'action' => $this->getRouter()->generate('notice.save', ['id' => $id]),
        ]);
        $form->handleRequest($request);

        return $this->render('NoticeBundle:Default:update.html.twig', [
            'form' => $form->createView(),
            'notice' => $notice,
        ]);
    }

    public function saveAction(Request $request, $id = 'new')
    {
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_NOTICE_CREATE']);
            $notice = new Notice();
        } else {
            $this->denyAccessUnlessGranted(['ROLE_NOTICE_UPDATE']);
            $notice = $this->getNoticeRepository()->find($id);
            if (!$notice) {
                throw new NotFoundHttpException(sprintf('Notice not found for "%s %s"', $request->getRealMethod(), $request->getPathInfo()));
            }
        }

        $form = $this->createForm(NoticeType::class, $notice);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $notice = $form->getData();

            $this->getNoticeRepository()->save($notice);
            if ('new' === $id) {
                $this->getNotificationHelper()->push($notice->getId(), 'notice', 'all');
            }

            $this->getSession()->getFlashBag()->add('notifications', [
                'type' => 'success',
                'title' => $this->getTranslator()->trans(
                    'notification.' . ($id === 'new' ? 'created' : 'updated') . '.title',
                    [],
                    'NoticeBundle'
                ),
                'message' => $this->getTranslator()->trans(
                    'notification.' . ($id === 'new' ? 'created' : 'updated') . '.message',
                    ['%title%' => $notice->getTitle()],
                    'NoticeBundle'
                ),
            ]);

            return $this->redirectToRoute('notice.update_page', ['id' => $notice->getId()]);
        }

        return $this->redirect($request->headers->get('referer'), 307);
    }

    public function viewAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_NOTICE_VIEW']);
        $this->getMenuManager()->setActive('notice.list');

        $notice = $this->getNoticeRepository()->find($id);

        if (!$notice) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(sprintf('Notice not found for "%s %s"', $request->getRealMethod(), $request->getPathInfo()));
        }

        $form = $this->createForm(NoticeType::class, $notice, [
            'action' => $this->getRouter()->generate('notice.save', ['id' => $id]),
            'mapped' => false,
        ]);
        $form->handleRequest($request);

        return $this->render('NoticeBundle:Default:view.html.twig', [
            'form' => $form->createView(),
            'notice' => $notice,
        ]);
    }

    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_NOTICE_DELETE']);
        $notice = $this->getNoticeRepository()->find($id);
        if ($notice) {
            $title = $notice->getTitle();
            $this->getNoticeRepository()->delete($notice);
            $this->getSession()->getFlashBag()->add('notifications', [
                'type' => 'success',
                'title' => $this->getTranslator()->trans('notification.delete.title', [], 'NoticeBundle'),
                'message' => $this->getTranslator()->trans('notification.delete.message', ['%title%' => $title], 'NoticeBundle'),
            ]);

            return $this->redirectToRoute('notice.list_page');
        }

        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(sprintf('Notice not found for "%s %s"', $request->getRealMethod(), $request->getPathInfo()));
    }

    /**
     * Get user repository.
     *
     * @return \DbBundle\Repository\NoticeRepository
     */
    protected function getNoticeRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Notice');
    }

    /**
     * Get notice manager.
     *
     * @return \NoticeBundle\Manager\NoticeManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('notice.manager');
    }
}
