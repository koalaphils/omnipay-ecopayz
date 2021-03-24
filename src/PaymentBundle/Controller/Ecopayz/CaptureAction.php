<?php

namespace PaymentBundle\Controller\Ecopayz;

use Payum\OmnipayV3Bridge\Action\OffsiteCaptureAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\Capture;
use DbBundle\Entity\Transaction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class CaptureAction extends OffsiteCaptureAction implements ActionInterface
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $details = ArrayObject::ensureArrayObject($request->getModel());
        if ($details['_status']) {
            return;
        }
        try {
            parent::execute($request);
        } catch (\Payum\Core\Reply\HttpRedirect $redirect) {
            $storage = $this->getPayum()->getStorage('PaymentBundle\Model\Payment');
            $model = $request->getModel();
            $parsedUrl = parse_url($redirect->getUrl(), PHP_URL_QUERY);
            parse_str($parsedUrl, $params);
            $model['checksum'] = $params['Checksum'];
            $storage->update($model);

            throw $redirect;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture
            && $request->getModel() instanceof \ArrayAccess
            && method_exists($this->omnipayGateway, 'purchase')
            && $request->getModel()['transaction'] instanceof Transaction
            && $request->getModel()['transaction']->isNew()
        ;
    }

    private function getPayum(): \Payum\Core\Payum
    {
        return $this->container->get('payum');
    }
}
