<?php

namespace ZendeskBundle\Manager;

use Symfony\Component\HttpFoundation\Request;
use Zendesk\API\Exceptions\ApiResponseException;

/**
 * Description of TicketManager.
 *
 * @author cnonog
 */
class TicketManager extends AbstractManager
{
    public function getList(Request $request)
    {
        $search = $request->get('search', null);
        $length = $request->get('length', 10);
        $start = $request->get('start', 0);
        $page = ceil($start / $length) + 1;

        $status = $request->get('status', null);

        if (is_array($search) && $request->get('datatable', 0)) {
            $search = array_get($search, 'value', null);
        }

        $query = [];

        if (!is_null($search)) {
            $query[] = '"' . $search . '"';
        }
        if (!is_null($status)) {
            $query[] = 'status' . $status;
        }

        $params = [
            'type' => 'ticket',
            'page' => $page,
            'per_page' => $length,
            'include' => ['via_id'],
            'query' => implode(' ', $query),
        ];
        $resultKey = 'results';
        if (0 != $request->get('view', 0)) {
            $result = $this->getZendeskAPI()->views()->execute(['id' => $request->get('view')]);
            $resultKey = 'rows';
        } else {
            $result = $this->getZendeskAPI()->get('search/incremental', $params);
        }

        /*$result->$result_key = array_map(function($ticket) use($request) {
            if(0 != $request->get('view', 0)) $ticket->ticket->url = $this->getRouter()->generate('ticket.view_page', array('id' => $ticket->ticket->id));
            else $ticket->url = $this->getRouter()->generate('ticket.view_page', array('id' => $ticket->id));
            return $ticket;
        }, $result->$result_key);*/

        $normlalizedResult = \ZendeskBundle\Adapter\ZendeskAdapter::create($result);
        $normlalizedResult->setPerPage($length, 'per_page');

        /*if($request->get('datatable', 0)) {
            $result->draw = $request->get('draw');
        }*/

        return $normlalizedResult->getOriginalObject();
    }

    public function createTicketEntity($theTicket = [])
    {
        $properties = $data;
        if ($data instanceof \stdClass) {
            $properties = get_object_vars($data);
        }

        $ticket = new \DbBundle\Entity\Ticket();
        foreach ($properties as $key => $value) {
            $formatedKey = studly_case($key);
            $methodName = "set" . $formatedKey;

            if (method_exists($ticket, $methodName)) {
                call_user_func([$ticket, $methodName], $value);
            }
        }

        return $ticket;
    }

    public function createTicket($theTicket = [])
    {
        $result = [];
        $formTicket = $theTicket['formTicket'];
        $userDetails = $theTicket['userDetails'];

        $newTicket = [
            'status' => $formTicket['status'],
            'type' => $formTicket['type'],
            'tags' => !empty($formTicket['tag']) ? explode(',', $formTicket['tag']) : '',
            'subject' => $formTicket['subject'],
            'comment' => [
                'html_body' => $formTicket['description'],
                'body' => htmlentities($formTicket['description']),
                'plain_body' => strip_tags($formTicket['description']),
                'author_id' => $this->getContainer()->getParameter('zendesk_assignee_id'),
            ],
            'requester' => [
                'locale_id' => $this->getContainer()->getParameter('zendesk_default_locale'),
                'name' => $userDetails->getUsername(),
                'email' => $userDetails->getEmail(),
            ],
            'priority' => $formTicket['priority'],
            'submitter_id' => $this->getContainer()->getParameter('zendesk_assignee_id'),
        ];
        $result = $this->getZendeskAPI()->tickets()->create($newTicket);

        return !empty($result->ticket) ? $result->ticket : null;
    }

    public function updateTicket($ticketId = null, $formTicket = [])
    {
        $result = [];
        $formTicket = $formTicket['formTicket'];

        $ticket = [
            'status' => $formTicket['status'],
            'type' => $formTicket['type'],
            'tags' => !empty($formTicket['tag']) ? explode(',', $formTicket['tag']) : '',
            'author_id' => $this->getContainer()->getParameter('zendesk_assignee_id'),
            'comment' => [
                'html_body' => $formTicket['description'],
                'body' => htmlentities($formTicket['description']),
                'plain_body' => strip_tags($formTicket['description']),
            ],
            'custom_fields' => [
                [
                    'id' => $this->getContainer()->getParameter('zendesk_ticket_is_read_id'),
                    'value' => 'no',
                ],
            ],
        ];

        $result = $this->getZendeskAPI()->tickets()->update($ticketId, $ticket);

        return !empty($result->ticket) ? $result->ticket : null;
    }

    public function findTicket($ticketId = null)
    {
        $ticket = $this->getZendeskAPI()->tickets()->find($ticketId);

        return !empty($ticket->ticket) ? $ticket->ticket : null;
    }

    public function getTicketComment($ticketId = null)
    {
        $ticket = $this->getZendeskAPI()->tickets($ticketId)->comments()->findAll();

        return !empty($ticket->comments) ? $ticket->comments : null;
    }

    public function deleteTicket($ticketId = null)
    {
        $ticket = $this->getZendeskAPI()->tickets()->delete($ticketId);

        return !empty($ticket->ticket) ? $ticket->ticket : null;
    }

    public function getUserByEmail($email = null)
    {
        $params = ['query' => $email];
        $user = $this->getZendeskAPI()->users()->search($params);

        return !empty($user->users) ? $user->users : null;
    }

    public function reconstructComments($array = [])
    {
        $status = true;
        $newArray = [];
        if (!empty($array)) {
            foreach ($array as $key => $comment) {
                $normalizedComment = \ZendeskBundle\Adapter\ZendeskAdapter::create($data);
                if ($normalizedComment->getAuthorId() == $this->getContainer()->getParameter('zendesk_assignee_id')) {
                    $newArray[] = [
                        'authorId' => $normalizedComment->getAuthorId(),
                        'body' => $normalizedComment->getBody(),
                        'htmlBody' => $normalizedComment->getHtmlBody(),
                        'plainBody' => $normalizedComment->getPlainBody(),
                        'createdAt' => $this->_timeElapsedString($normalizedComment->getCreatedAt()),
                        'fullName' => $this->getContainer()->getParameter('zendesk_assignee'),
                        'email' => $this->getContainer()->getParameter('zendesk_assignee_email'),
                    ];
                } else {
                    $customer = $this->_getCustomerRepository()->findRequesterByZendeskId($normalizedComment->getAuthorId());
                    $newArray[] = [
                        'authorId' => $normalizedComment->getAuthorId(),
                        'body' => $normalizedComment->getBody(),
                        'htmlBody' => $normalizedComment->getHtmlBody(),
                        'plainBody' => $normalizedComment->getPlainBody(),
                        'createdAt' => $this->_timeElapsedString($normalizedComment->getCreatedAt()),
                        'fullName' => $customer->getFname() . ' ' . $customer->getLname(),
                        'email' => $customer->getUser()->getEmail(),
                    ];
                }
            }
        } else {
            $newArray = $array;
        }

        return $newArray;
    }

    public function markAsRead($ticketId = null, $return = false)
    {
        //FOR COUNTER and UNREAD TICKET
        $module = 'ticket';
        $code = 'counter';
        $x = 0;

        $unreadIds = $this->getUnreadIds();
        $counter = json_decode($this->getSettingRepository()->getSettingValue(), true);

        $newCounter = (int) $counter[$module] - $x = (int) $this->substrCountOverlap($unreadIds, ',' . $ticketId . ',');
        if ($x) {
            $this->getSettingRepository()->updateSetting($module, $code, $newCounter);
            //REMOVE TO UNREAD LIST
            $unreadIds = str_replace($ticketId, '', $unreadIds);
            $unreadIds = preg_replace('/,{2,}/', ',', $unreadIds);
            $this->getSettingRepository()->updateSetting($module, 'unread.id', preg_match('/[0-9]/', $unreadIds) ? $unreadIds : '');
        }
        if ($return) {
            return $newCounter;
        }
    }

    public function getUnreadIds()
    {
        $result = [];
        $unreadIds = json_decode($this->getSettingRepository()->getSettingValue('unread.id'), true);
        $result = $unreadIds['ticket'];

        return $result;
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
     * Get setting repository.
     *
     * @return \DbBundle\Repository\SettingRepository
     */
    private function getSettingRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Setting');
    }

    private function substrCountOverlap($string, $target)
    {
        $count = 0;
        $start = 0;
        while (1) {
            $found = strpos($string, $target, $start);
            if ($found !== false) {
                ++$count;
                $start = $found + 1;
            } else {
                return $count;
            }
        }

        return $count;
    }

    private function _timeElapsedString($dateTime, $full = false)
    {
        $now = new \DateTime();
        $ago = new \DateTime($dateTime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = [
            'y' => $this->getTranslator()->trans('elapsedTime.year', [], 'TicketBundle'),
            'm' => $this->getTranslator()->trans('elapsedTime.month', [], 'TicketBundle'),
            'w' => $this->getTranslator()->trans('elapsedTime.week', [], 'TicketBundle'),
            'd' => $this->getTranslator()->trans('elapsedTime.day', [], 'TicketBundle'),
            'h' => $this->getTranslator()->trans('elapsedTime.hour', [], 'TicketBundle'),
            'i' => $this->getTranslator()->trans('elapsedTime.minute', [], 'TicketBundle'),
            's' => $this->getTranslator()->trans('elapsedTime.second', [], 'TicketBundle'),
        ];
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? $this->getTranslator()->trans('elapsedTime.plural', [], 'TicketBundle') : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) {
            $string = array_slice($string, 0, 1);
        }

        if ($string) {
            return implode(', ', $string) . ' ' . $this->getTranslator()->trans('elapsedTime.ago', [], 'TicketBundle');
        }

        return ' ' . $this->getTranslator()->trans('elapsedTime.justnow', [], 'TicketBundle');
    }
}
