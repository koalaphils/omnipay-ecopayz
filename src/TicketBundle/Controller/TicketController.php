<?php

namespace TicketBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Zendesk\API\Exceptions\ApiResponseException;
use TicketBundle\Form\ConfirmType;
use TicketBundle\Form\TicketType;
use DbBundle\Entity\Ticket;
use AppBundle\Helper\NotificationHelper;

//use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

/**
 * Description of ViewController.
 *
 * @author cnonog and cpabendanio
 */
class TicketController extends AbstractController
{
    public function indexAction(Request $request)
    {
        $unreadIds = $this->getManager()->getUnreadIds();
        $this->denyAccessUnlessGranted(['ROLE_TICKET_VIEW']);
        $form = $this->createForm(ConfirmType::class, null, [
            //'action' => $this->getRouter()->generate('ticket.delete')
        ]);
        //$form->handleRequest($request);

        $result = $this->render('TicketBundle:Default:index.html.twig', [
            'confirmForm' => $form->createView(),
            'unreadIds' => !empty($unreadIds) ? $unreadIds : '',
        ]);

        return $result;
    }

    public function createAction(Request $request)
    {
        $result = [];

        $this->denyAccessUnlessGranted(['ROLE_TICKET_CREATE']);
        $form = $this->createForm(TicketType::class, null, [
            'action' => $this->getRouter()->generate('ticket.save'),
        ]);
        $form->handleRequest($request);

        $result = $this->render('TicketBundle:Default:create.html.twig', [
            'form' => $form->createView(),
        ]);

        return $result;
    }

    public function saveAction(Request $request, $tid = 'new', $rid = null)
    {
        $status = true;
        $validationGroups = ['default'];
        $ticketData = [];
        $this->denyAccessUnlessGranted(['ROLE_TICKET_CREATE', 'ROLE_TICKET_REPLY']);
        $ticket = new Ticket();

        if ($tid !== 'new') {
            $req = $request->request->get('Ticket');
            $ticket->setRequester($rid);
            $ticket->setAssignee($this->container->getParameter('zendesk_assignee'));
            $ticket->setType($req['type']);
            $ticket->setPriority($req['priority']);
            $ticket->setSubject($this->container->getParameter('zendesk_assignee'));
            $ticket->setDescription($req['description']);
            $ticket->setTag(explode(',', $req['tag']));
        }

        $form = $this->createForm(TicketType::class, $ticket, [
            'validation_groups' => $validationGroups,
        ]);
        if ($tid !== 'new') {
            $form->remove('requester');
            $form->remove('assignee');
            $form->remove('subject');
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketData['formTicket'] = $request->request->get('Ticket');

            $requester = null;
            if ($tid === 'new') {
                $requester = $ticketData['userDetails'] = $this->_getUserRepository()->getUserByZendeskId($ticketData['formTicket']['requester']);
            } else {
                $requester = $this->_getUserRepository()->getUserByZendeskId($rid);
            }

            $oldTicket = null;
            $action = 'new';
            if ('new' === $tid) {
                $newTicket = $this->getManager()->createTicket($ticketData);
            } else {
                $action = 'update';
                $oldTicket = $this->getManager()->findTicket($tid);
                $newTicket = $this->getManager()->updateTicket($tid, $ticketData);
                $this->getManager()->markAsRead($tid);
            }

            if (empty($newTicket)) {
                return $this->redirect($request->headers->get('referer'), 307);
            }

            $this->getNotificationHelper()->updateCounter($requester, NotificationHelper::CHANNEL_MESSAGE, $oldTicket, $action);
            $this->getNotificationHelper()->push($newTicket->id, NotificationHelper::CHANNEL_MESSAGE, $requester, $action);

            $this->getSession()->getFlashBag()->add('notifications', [
                'title' => $this->getTranslator()->trans('notification.title', [], 'TicketBundle'),
                'message' => $this->getTranslator()->trans('notification.' . ($tid === 'new' ? 'created' : 'sent'), [], 'TicketBundle'),
            ]);

            $normalizeTicket = \ZendeskBundle\Adapter\ZendeskAdapter::create($newTicket);

            return $this->redirectToRoute('ticket.reply_page', ['tid' => $normalizeTicket->getId(), 'rid' => $normalizeTicket->getRequesterId()]);
        }

        return $this->redirect($request->headers->get('referer'), 307);
    }

    public function replyAction(Request $request, $tid = null, $rid = null)
    {
        $status = true;
        $result = $user = $ticket = $commentData = [];

        $this->denyAccessUnlessGranted(['ROLE_TICKET_REPLY']);
        if (empty($tid) || empty($rid)) {
            return $this->redirect($request->headers->get('referer'), 307);
        }

        $this->getMenuManager()->setActive('ticket.create');

        $ticketData = $this->getManager()->findTicket($tid);

        $commentData = $this->getManager()->reconstruct_comments($this->getManager()->getTicketComment($tid));

        $ticket = new Ticket();
        $ticket->setTicketId($tid);
        $ticket->setRequester($rid);
        $ticket->setAssignee($this->container->getParameter('zendesk_assignee'));
        $ticket->setStatus($ticketData->status);
        $ticket->setType($ticketData->type);
        $ticket->setPriority($ticketData->priority);
        $ticket->setSubject($ticketData->subject);
        $ticket->setDescription($ticketData->description);
        $ticket->setTag($ticketData->tags);

        $this->getManager()->markAsRead($tid);

        //$customer =  $this->_getCustomerRepository()->findCustomerByZendeskId($rid);

        $form = $this->createForm(TicketType::class, $ticket, [
            'action' => $this->getRouter()->generate('ticket.save', ['tid' => $tid, 'rid' => $rid]),
            'assignee' => $ticket->getAssignee(),
        ]);
        $form->handleRequest($request);

        $result = $this->render('TicketBundle:Default:reply.html.twig', [
            'form' => $form->createView(),
            'comments' => $commentData,
        ]);

        return $result;
    }

    public function deleteAction(Request $request)
    {
        $result = [];
        $status = true;
        $message = null;
        $statusCode = JsonResponse::HTTP_OK;

        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse($result, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
        $this->denyAccessUnlessGranted(['ROLE_TICKET_DELETE']);
        $ticketId = $request->request->get('ticketId');

        $ticketData = $this->getManager()->deleteTicket($ticketId);
        if (empty($ticketData)) {//empty because no return
            $result['counter'] = $this->getManager()->markAsRead($ticketId, true);
            $message = [
                'type' => 'success',
                'title' => $this->getTranslator()->trans('notification.title', [], 'TicketBundle'),
                'text' => $this->getTranslator()->trans('notification.deleted', [], 'TicketBundle'),
            ];
        }

        return new JsonResponse([
            'status' => $status,
            'message' => $message,
            'result' => $result,
        ], $statusCode);
    }

    /**
     * Get setting repository.
     *
     * @return \DbBundle\Repository\SettingRepository
     */
    protected function getSettingRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Setting');
    }

    /**
     * Get view manager.
     *
     * @return \ZendeskBundle\Manager\TicketManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('zendesk.ticket_manager');
    }

    /**
     * Get user repository.
     *
     * @return \DbBundle\Repository\UserRepository
     */
    private function _getUserRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:User');
    }

    /**
     * Get user repository.
     *
     * @return \DbBundle\Repository\CustomerRepository
     */
    private function _getCustomerRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Customer');
    }

    /**
     * Get ticket repository.
     *
     * @return \DbBundle\Repository\TicketRepository
     */
    private function _getTicketRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Ticket');
    }
}
