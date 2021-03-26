<?php

declare(strict_types = 1);

namespace ApiBundle\Controller;

use ApiBundle\Request\Transaction\DepositRequest;
use ApiBundle\Request\Transaction\GetLastBitcoinRequest;
use ApiBundle\Request\Transaction\TransferRequest;
use ApiBundle\Request\Transaction\WithdrawRequest;
use ApiBundle\RequestHandler\Transaction\DepositHandler;
use ApiBundle\RequestHandler\Transaction\TransactionCommandHandler;
use ApiBundle\RequestHandler\Transaction\TransactionQueryHandler;
use ApiBundle\RequestHandler\Transaction\TransferHandler;
use ApiBundle\RequestHandler\Transaction\WithdrawHandler;
use DbBundle\Entity\Transaction;
use Doctrine\ORM\NoResultException;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TransactionController extends AbstractController
{
    /**
     *  @ApiDoc(
     *     description="Request deposit transaction",
     *     section="Transaction",
     *     views={"piwi"},
     *     requirements={
     *         {"name"="payment_option_type", "dataType"="string"},
     *         {"name"="products[0][username]", "dataType"="string"},
     *         {"name"="products[0][product_code]", "dataType"="string"},
     *         {"name"="products[0][amount]", "dataType"="string"},
     *         {"name"="meta[field][email]", "dataType"="string"},
     *     },
     *     parameters={
     *         {"name"="payment_option", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][range_start]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][range_end]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][adjustment]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][adjustment_type]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][blockchain_rate]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate]", "dataType"="string", "required"=false},
     *         {"name"="products[0][meta][payment_details][bitcoin][requested_btc]", "dataType"="string", "required"=false}
     *     },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function depositAction(Request $request, DepositHandler $depositHandler, ValidatorInterface $validator): View
    {
        $member = $this->getUser()->getCustomer();
        $depositRequest = DepositRequest::createFromRequest($request);
        $depositRequest->setMemberId((int) $member->getId());

        $violations = $validator->validate($depositRequest, null);

        if ($violations->count() > 0) {
            return $this->view($violations);
        }

        $transaction = $depositHandler->handle($depositRequest);

        return $this->view($transaction);
    }

    /**
     * @ApiDoc(
     *     description="Request withdraw transaction",
     *     section="Transaction",
     *     views={"piwi"},
     *     requirements={
     *         {"name"="verification_code", "dataType"="string"},
     *         {"name"="payment_option_type", "dataType"="string"},
     *         {"name"="products[0][username]", "dataType"="string"},
     *         {"name"="products[0][product_code]", "dataType"="string"},
     *         {"name"="products[0][amount]", "dataType"="string"}
     *     },
     *     parameters={
     *         {"name"="payment_option", "dataType"="string", "required"=false},
     *         {"name"="meta[field][email]", "dataType"="string", "required"=false},
     *         {"name"="meta[field][account_id]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][range_start]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][range_end]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][adjustment]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][adjustment_type]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][blockchain_rate]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate]", "dataType"="string", "required"=false},
     *         {"name"="products[0][meta][payment_details][bitcoin][requested_btc]", "dataType"="string", "required"=false}
     *     },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function withdrawAction(Request $request, WithdrawHandler $withdrawHandler, ValidatorInterface $validator): View
    {
        $member = $this->getUser()->getCustomer();
        $withdrawRequest = WithdrawRequest::createFromRequest($request);
        $withdrawRequest->setMember($member);
        $violations = $validator->validate($withdrawRequest, null);
        if ($violations->count() > 0) {
            return $this->view($violations);
        }
        $transaction = $withdrawHandler->handle($withdrawRequest);

        return $this->view($transaction);
    }

    /**
     * @ApiDoc(
     *     description="Request transfer transaction",
     *     section="Transaction",
     *     views={"piwi"},
     *     requirements={
     *         {"name"="payment_option_type", "dataType"="string"},
     *         {"name"="from[username]", "dataType"="string"},
     *         {"name"="from[product_code]", "dataType"="string"},
     *         {"name"="from[amount]", "dataType"="string"},
     *         {"name"="to[0][username]", "dataType"="string"},
     *         {"name"="to[0][product_code]", "dataType"="string"},
     *         {"name"="to[0][amount]", "dataType"="string"}
     *     },
     *     parameters={
     *         {"name"="payment_option", "dataType"="string", "required"=false},
     *         {"name"="meta[field][email]", "dataType"="string", "required"=false},
     *         {"name"="meta[field][account_id]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][range_start]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][range_end]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][adjustment]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][adjustment_type]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][blockchain_rate]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate]", "dataType"="string", "required"=false},
     *         {"name"="products[0][meta][payment_details][bitcoin][requested_btc]", "dataType"="string", "required"=false}
     *     },
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function transferAction(Request $request, TransferHandler $handler, ValidatorInterface $validator): JsonResponse
    {
        $member = $this->getUser()->getCustomer();
        $request = new TransferRequest($member, $request);
        $violations = $validator->validate($request, null);
        if ($violations->count() > 0) {
            return $this->view($violations);
        }

        $response = $handler->handle($request);

        return new JsonResponse($response);
    }

    /**
     * @ApiDoc(
     *     views={"piwi"},
     *     section="Transaction",
     *     description="Get last bitcoin transaction",
     *     headers={{ "name"="Authorization", "description"="Bearer <access_token>" }}
     * )
     *
     * @param TokenStorage $tokenStorage
     * @param TransactionQueryHandler $handler
     * @param string $type
     * @return View
     */
    public function getLastBitcoinTransactionAction(TokenStorage $tokenStorage, TransactionQueryHandler $handler, string $type): View
    {
        $member = $tokenStorage->getToken()->getUser()->getCustomer();

        $request = new GetLastBitcoinRequest((int) $member->getId(), $type);

        $transaction = $handler->handleGetLastBitcoin($request);

        $view = $this->view(['data' => $transaction]);
        $view->getContext()->setGroups(['Default', 'details']);

        return $view;
    }

    /**
     * @ApiDoc(
     *     description="Acknowledge a bitcoin transaction.",
     *     views={"piwi"},
     *     section="Transaction",
     *     headers={{ "name"="Authorization", "description"="Bearer <access_token>" }}
     * )
     */
    public function acknowledgeBitcoinTransactionAction(TokenStorage $tokenStorage, TransactionCommandHandler $handler): View
    {
        $member = $tokenStorage->getToken()->getUser()->getCustomer();
        try {
            $handler->handleAcknowledgeBitcoin($member);

            return $this->view(['success' => true]);
        } catch (NoResultException $exception) {
            return $this->view(['success' => false, 'error' => 'No active transaction to acknowledge.'], Response::HTTP_BAD_REQUEST);
        }

    }
}