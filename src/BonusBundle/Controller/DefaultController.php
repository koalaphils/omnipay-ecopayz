<?php

namespace BonusBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use BonusBundle\Form\BonusType;
use DbBundle\Entity\Bonus;
use DbBundle\Entity\Transaction;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_BONUS_VIEW']);

        return $this->render('BonusBundle:Default:index.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_BONUS_VIEW']);
        $filters = $request->request->all();
        $results = $this->getManager()->getBonusList($filters);

        return new JsonResponse($results, JsonResponse::HTTP_OK);
    }

    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_BONUS_CREATE']);
        $bonus = new Bonus();
        $form = $this->createForm(BonusType::class, $bonus, ['action' => $this->getRouter()->generate('bonus.save')]);
        $form->handleRequest($request);

        return $this->render('BonusBundle:Default:create.html.twig', ['form' => $form->createView()]);
    }

    public function saveAction(Request $request, $id = 'new')
    {
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_BONUS_CREATE']);
            $bonus = new Bonus();
        } else {
            $this->denyAccessUnlessGranted(['ROLE_BONUS_UPDATE']);
            $bonus = $this->getManager()->getRepository()->find($id);
        }

        $form = $this->createForm(BonusType::class, $bonus, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bonus = $form->getData();
            $this->getManager()->getRepository()->save($bonus);

            $this->getSession()->getFlashBag()->add('notifications', [
                'type' => 'success',
                'title' => $this->getTranslator()->trans(
                    'notification.' . ($id === 'new' ? 'created' : 'updated') . '.title',
                    [],
                    'BonusBundle'
                ),
                'message' => $this->getTranslator()->trans(
                    'notification.' . ($id === 'new' ? 'created' : 'updated') . '.message',
                    ['%subject%' => $bonus->getSubject()],
                    'BonusBundle'
                ),
            ]);

            return $this->redirectToRoute('bonus.update_page', ['id' => $bonus->getId()]);
        }

        return $this->redirect($request->headers->get('referer'), 307);
    }

    public function updateAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_BONUS_UPDATE']);

        $bonus = $this->getManager()->getRepository()->find($id);

        $form = $this->createForm(BonusType::class, $bonus, [
            'action' => $this->getRouter()->generate('bonus.save', ['id' => $bonus->getId()]),
        ]);
        $form->handleRequest($request);

        return $this->render('BonusBundle:Default:update.html.twig', [
            'form' => $form->createView(),
            'bonus' => $bonus,
        ]);
    }

    public function deleteAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_BONUS_DELETE']);
        if ($bonus = $this->getManager()->getRepository()->find($id)) {
            $this->getManager()->getRepository()->delete($bonus);

            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', [
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans('notification.deleted.title', [], 'BonusBundle'),
                    'message' => $this->getTranslator()->trans(
                        'notification.deleted.message',
                        ['%subject%' => $bonus->getSubject()],
                        'BonusBundle'
                    ),
                ]);

                return $this->redirectToRoute('bonus.list_page');
            }

            return new JsonResponse([
                '__notifications' => [[
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans('notification.deleted.title', [], 'BonusBundle'),
                    'message' => $this->getTranslator()->trans(
                        'notification.deleted.message',
                        ['%subject%' => $bonus->getSubject()],
                        'BonusBundle'
                    ),
                ], ],
            ], 200);
        } else {
            throw $this->createNotFoundException(
                sprintf('Bonus not found for "%s %s"', $request->getRealMethod(), $request->getPathInfo())
            );
        }
    }

    public function readAction()
    {
        $result = [];
        $type = ['type' => [Transaction::TRANSACTION_TYPE_BONUS]];

        $status = $this->getTransactionRepository()->updateTransactionPreference($key = 'isRead', $type);
        $result = ['status' => $status];

        return new JsonResponse($result, $status ? JsonResponse::HTTP_OK : JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @return \BonusBundle\Manager\BonusManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('bonus.manager');
    }

    /**
     * Get transaction repository.
     *
     * @return \DbBundle\Repository\TransactionRepository
     */
    protected function getTransactionRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Transaction');
    }
}
