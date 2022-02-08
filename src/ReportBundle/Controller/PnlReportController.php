<?php

namespace ReportBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PnlReportController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_REPORT_PRODUCT_VIEW']);
        $this->getSession()->save();

        return $this->render('ReportBundle:Report:pnl/pnl-report.html.twig');
    }
}
