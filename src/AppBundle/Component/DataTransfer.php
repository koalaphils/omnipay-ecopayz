<?php

namespace AppBundle\Component;

use AppBundle\Exceptions\CoreException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\JsonResponse;

class DataTransfer
{
    private $dataTransfers;
    private $cache;

    public function __construct($container)
    {
        $this->container = $container;
        $this->dataTransfers = [];
        $this->cache = [];
    }

    public function register(array $dataTransfers)
    {
        $this->dataTransfers = $dataTransfers;
    }

    public function transform($dataTransferClass, $data, $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureTransformOptions($resolver);
        $options = $resolver->resolve($options);

        if (!isset($this->dataTransfers[$dataTransferClass])) {
            throw new CoreException("{$dataTransferClass} class is not registered as a service with correct DataTransfer tag (zimi.data_transfer).");
        }

        $dataTransfer = null;
        if (isset($this->cache[$dataTransferClass])) {
            $dataTransfer = $this->cache[$dataTransferClass];
        } else {
            $serviceId = $this->dataTransfers[$dataTransferClass];
            $dataTransfer = $this->container->get($serviceId);
            $cache[$dataTransferClass] = $dataTransfer;
        }

        $result = $dataTransfer->transform($data, $options);

        return $result;
    }

    private function configureTransformOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            '_format' => 'json',
        ]);
    }

    private function toJson($data)
    {
        return json_encode($data);
    }

    private function toXML($data, $dataTransfer)
    {
        $view = $this->container->getParameter('data_transfer.xml');

        if (!$this->container->has('twig')) {
            throw new \LogicException('You can not use the "renderView" method if the Templating Component or the Twig Bundle are not available.');
        }

        return $this->container->get('twig')->render($view, ['result' => $data]);
    }
}
