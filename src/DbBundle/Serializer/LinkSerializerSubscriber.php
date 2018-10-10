<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DbBundle\Serializer;

use Doctrine\Bundle\DoctrineBundle\Registry;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Routing\Router;
use DbBundle\Entity;
use Symfony\Component\HttpFoundation\RequestStack;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Context;

/**
 * Description of LinkSerializerSubscriber
 *
 * @author cnonog
 */
class LinkSerializerSubscriber implements \JMS\Serializer\EventDispatcher\EventSubscriberInterface
{
    private $router;
    private $expressionLanguage;
    private $requestStack;
    private $doctrine;

    public function __construct(Router $router, RequestStack $requestStack, Registry $doctrine)
    {
        $this->router = $router;
        $this->expressionLanguage = new ExpressionLanguage();
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
    }

    public function onPostSerializeMethod(ObjectEvent $object)
    {
        /* @var $context \JMS\Serializer\SerializationContext */
        $context = $object->getContext();
        $include = true;
        if ($context->attributes->get('groups') instanceof \PhpOption\None) {
            return;
        }
        $groups = $this->getGroupsFor($context->attributes->get('groups')->get(), $context);
        if (in_array('_link', $groups)) {
            $entity = $object->getObject();
            /* @var $visitor VisitorInterface */
            $visitor = $object->getVisitor();

            $routeInfo = $this->getRouteInfoByClass(get_class($entity));
            if ($routeInfo !== null) {
                $params = $routeInfo['params'];
                $routeParams = $this->resolveParams($params, $entity);
                $link = $this->router->generate($routeInfo['name'], $routeParams);

                $visitor->addData('@link', $link);
                $visitor->addData('_link', [
                    'page' => $link,
                ]);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerializeMethod'],
        ];
    }

    protected function getRouteInfoByClass($className)
    {
        $classRouteNames = [
            Entity\Customer::class => [
                'name' => 'member.update_page',
                'params' => ['id' => 'object.id', '_locale' => 'request._locale'],
            ],
            Entity\User::class => [
                'name' => 'user.update_page',
                'params' => ['id' => 'object.id', '_locale' => 'request._locale'],
            ],
            Entity\Gateway::class => [
                'name' => 'gateway.update_page',
                'params' => ['id' => 'object.id', '_locale' => 'request._locale'],
            ],
            Entity\Country::class => [
                'name' => 'country.update_page',
                'params' => ['id' => 'object.id', '_locale' => 'request._locale'],
            ],
            Entity\Currency::class => [
                'name' => 'currency.update_page',
                'params' => ['id' => 'object.id', '_locale' => 'request._locale'],
            ],
            Entity\Transaction::class => [
                'name' => 'transaction.update_page',
                'params' => ['type' => 'object.typeText', 'id' => 'object.id', '_locale' => 'request._locale'],
            ],
            Entity\PaymentOption::class => [
                'name' => 'paymentoption.update_page',
                'params' => ['id' => 'object.code', '_locale' => 'request._locale'],
            ],
            Entity\DWL::class => [
                'name' => 'dwl.update_page',
                'params' => ['id' => 'object.id'],
            ],
            Entity\GatewayTransaction::class => [
                'name' => 'gateway_transaction.update_page',
                'params' => ['type' => 'object.typeText', 'id' => 'object.id', '_locale' => 'request._locale'],
            ],
            Entity\GatewayLog::class => [
                'name' => 'gateway_log.redirect_page',
                'params' => ['id' => 'object.id', '_locale' => 'request._locale'],
            ],
            Entity\AuditRevisionLog::class => [
                'name' => 'audit.redirect_page',
                'params' => ['id' => 'object.id', '_locale' => 'request._locale'],
            ],
            Entity\RiskSetting::class => [
                'name' => 'customer.risk_setting.update',
                'params' => ['id' => 'object.id', '_locale' => 'request._locale'],
            ]
        ];

        return array_get($classRouteNames, $className);
    }

    private function resolveParams(array $params, $object)
    {
        $resolvedParams = [];
        $currentRequest = $this->requestStack->getCurrentRequest();
        foreach ($params as $key => $param) {
            $explodedParam = explode('.', $param, 2);
            if ($explodedParam[0] === 'object') {
                $methodName = 'get' . ucwords(str_replace('_', '', $explodedParam[1]));
                $resolvedParams[$key] = $object->{$methodName}();
            } elseif ($explodedParam[0] === 'request') {
                $resolvedParams[$key] = $currentRequest->attributes->get('_route_params')[$explodedParam[1]];
            }
        }

        return $resolvedParams;
    }

    private function getGroupsFor($groups, Context $navigatorContext)
    {
        $paths = $navigatorContext->getCurrentPath();
        foreach ($paths as $index => $path) {
            if (!array_key_exists($path, $groups)) {
                if ($index > 0) {
                    $groups = array('Default');
                }

                break;
            }

            $groups = $groups[$path];
        }

        return $groups;
    }
}
