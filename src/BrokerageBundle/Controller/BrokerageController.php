<?php

namespace BrokerageBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Entity\CustomerProduct;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;
use ApiBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class BrokerageController extends AbstractController
{
    public function removeSyncIdAction(Request $request)
    {
        $params = $request->request->all();
        $customerProduct = $this->getCustomerProductRepository()->findOneByBetSyncId($params['sync_id']);

        if ($customerProduct instanceOf CustomerProduct) {
            $response = $this->getBrokerageManager()->unlinkWithSkypeBetting($customerProduct);
        } else {
            $response = [
                "success" => false, 
                "message" => "customer product not exist",
            ];
        }

        return $response;
    }

    /**
     *
     * @ApiDoc(
     *  description="post bet transaction",
     *  resource=true, 
     *  authentication=true,
     *  filters={
     *      {
     *          "name"="sync_id",
     *          "dataType"="integer",
     *          "required"=true,
     *          "description"="Brokerage Id of Customer"
     *      },
     *  }
     * )
     */
    public function postBetsAction(Request $request)
    {
        $params = $request->request->all();
        $customerProduct = $this->getCustomerProductRepository()->findOneByBetSyncId($params['sync_id']);

        if ($customerProduct instanceOf CustomerProduct) {
            $response = $this->getBrokerageManager()->syncPostBets($customerProduct, $params['bets']);

            if ($response['success'] && $params['settled']) {
                $response = $this->getWinLossManager()->syncWinLoss($params['winLossInfo']);
            }
        } else {
            $response = [
                "success" => false, 
                "message" => "customer product not exist",
            ];
        }

        return $this->view($response);
    }

    /**
     *
     * @ApiDoc(
     *  description="To update transaction amount",
     *  resource=true,
     *  authentication=true,
     *  filters={
     *      {
     *          "name"="id",
     *          "dataType"="integer",
     *          "required"=true,
     *          "description"="Brokerage Id of Customer"
     *      }
     *  }
     * )
     * @param Request $request
     */
    public function updateBetAction(Request $request)
    {
        $response = $this->getBrokerageManager()->updateBetTransaction($request->request->all());

        return $this->view($response);
    }

    /**
     *
     * @ApiDoc(
     *  description="To update bet transaction details",
     *  resource=true,
     *  authentication=true,
     * )
     * @param Request $request
     */
    public function updateBetDetailsByEventAction(Request $request)
    {
        $response = $this->getBrokerageManager()->updateBetTransactionDetailsByEvent($request->request->all());

        return $this->view($response);
    }

    /**
     *
     * @ApiDoc(
     *  description="To update transaction amount",
     *  resource=true,
     *  authentication=true,
     *  filters={
     *      {
     *          "name"="id",
     *          "dataType"="integer",
     *          "required"=true,
     *          "description"="Brokerage Id of Customer"
     *      }
     *  }
     * )
     * @param Request $request
     */
    public function voidBetAction(Request $request)
    {
        $params = $request->request->all();
        $response = $this->getBrokerageManager()->voidBetTransaction($params);

        if ($response['success'] && $params['settled']) {
            $response = $this->getWinLossManager()->syncWinLoss($params['winLossInfo']);
        }

        return $this->view($response);
    }

    /**
     *
     * @ApiDoc(
     *  description="To update transaction amount",
     *  resource=true,
     *  authentication=true,
     *  filters={
     *      {
     *          "name"="id",
     *          "dataType"="integer",
     *          "required"=true,
     *          "description"="Brokerage Id of Customer"
     *      }
     *  }
     * )
     * @param Request $request
     */
    public function voidBetsByEventAction(Request $request)
    {
        $params = $request->request->all();
        $response = $this->getBrokerageManager()->voidBetTransactionsByEvent($params);

        if ($response['success'] && $params['settled']) {
            $response = $this->getWinLossManager()->syncWinLoss($params['winLossInfo']);
        }

        return $this->view($response);
    }

    public function addWinlossItemAction(Request $request)
    {
        $response = $this->getWinLossManager()->syncWinLoss($request->request->all());

        return $this->view($response);
    }

    public function updateWinlossItemAction(Request $request)
    {
        $response = $this->getWinLossManager()->syncWinLoss($request->request->all());

        return $this->view($response);
    }

    public function updateDateWinLossItemAction(Request $request)
    {
        $params = $request->request->all();
        $response = $this->getWinLossManager()->syncWinLoss($params['previousInfo']);

        if ($response['success']) { 
            $response = $this->getWinLossManager()->syncWinLoss($params['currentInfo']);
        }

        return $this->view($response);
    }

    public function searchNameAction(Request $request)
    {
        $response = $this->getBrokerageManager()->searchName($request->request->get('search'));

        return new JsonResponse($response, JsonResponse::HTTP_OK);
    }

    private function getBrokerageManager(): \BrokerageBundle\Manager\BrokerageManager
    {
        return $this->container->get('brokerage.brokerage_manager');
    }

    private function getCustomerProductRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerProduct');
    }

    private function getWinLossManager(): \BrokerageBundle\Manager\WinLossManager
    {
        return $this->container->get('brokerage.winloss_manager');
    }
}