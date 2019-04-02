<?php

declare(strict_types = 1);

namespace ApiBundle\Controller;

use ApiBundle\Request\Transaction\DepositRequest;
use ApiBundle\Request\Transaction\WithdrawRequest;
use ApiBundle\RequestHandler\Transaction\DepositHandler;
use ApiBundle\RequestHandler\Transaction\WithdrawHandler;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
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
     *         {"name"="meta[payment_details][bitcoin][block_chain_rate]", "dataType"="string", "required"=false},
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
        $depositRequest = DepositRequest::createFromRequest($request);
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
     *         {"name"="payment_option_type", "dataType"="string"},
     *         {"name"="products[0][username]", "dataType"="string"},
     *         {"name"="products[0][product_code]", "dataType"="string"},
     *         {"name"="products[0][amount]", "dataType"="string"},
     *         {"name"="meta[field][email]", "dataType"="string"}
     *     },
     *     parameters={
     *         {"name"="payment_option", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][receiver_unique_address]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][range_start]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][range_end]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][adjustment]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][rate_detail][adjustment_type]", "dataType"="string", "required"=false},
     *         {"name"="meta[payment_details][bitcoin][block_chain_rate]", "dataType"="string", "required"=false},
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
        $withdrawRequest = WithdrawRequest::createFromRequest($request);
        $violations = $validator->validate($withdrawRequest, null);
        if ($violations->count() > 0) {
            return $this->view($violations);
        }
        $transaction = $withdrawHandler->handle($withdrawRequest);

        return $this->view($transaction);
    }
}