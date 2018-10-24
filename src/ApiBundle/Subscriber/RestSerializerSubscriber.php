<?php

namespace ApiBundle\Subscriber;

use Doctrine\Bundle\DoctrineBundle\Registry;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Routing\Router;
use DbBundle\Entity;
use Symfony\Component\HttpFoundation\RequestStack;
use JMS\Serializer\VisitorInterface;
use JMS\Serializer\Context;

class RestSerializerSubscriber implements \JMS\Serializer\EventDispatcher\EventSubscriberInterface
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
        if (in_array('API', $groups)) {
            $entity = $object->getObject();
            /* @var $visitor VisitorInterface */
            $visitor = $object->getVisitor();

            $routeInfo = $this->getRouteInfoByClass(get_class($entity));
            if ($routeInfo !== null) {
                $route = $this->router->getRouteCollection()->get($routeInfo['name']);
                $params = $routeInfo['params'];
                $routeParams = $this->resolveParams($params, $entity);
                $link = $this->router->generate($routeInfo['name'], $routeParams);

                if (array_has($routeInfo, 'linkMethod')) {
                    $visitor->addData('_links', $this->{$routeInfo['linkMethod']}($object, $link));
                } else {
                    $visitor->addData('_links', [
                        'self' => [ "href" => $link ],
                    ]);
                }
            }
        }
    }

    public function customerLinks(ObjectEvent $object, string $link): array
    {
        return [
            'self' => [ 'href' => $link ],
            'products' => [ 'href' => $this->router->generate('api_customers.me_products') ],
        ];
    }

    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerializeMethod', 'class' => Entity\CustomerProduct::class],
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerializeMethod', 'class' => Entity\Customer::class],
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerializeMethod', 'class' => Entity\Transaction::class],
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerializeMethod', 'class' => Entity\Country::class],
        ];
    }

    private function includeLink(ObjectEvent $object): bool
    {
        $context = $object->getContext();
        $include = true;
        if ($context->attributes->get('groups') instanceof \PhpOption\None) {
            return false;
        }
        $groups = $this->getGroupsFor($context->attributes->get('groups')->get(), $context);

        if (!in_array('API', $groups)) {
            return false;
        }

        $entity = $object->getObject();

        return $this->getRouteInfoByClass(get_class($entity)) !== null;
    }

    private function getRouteInfoByClass($className)
    {
        $classRouteNames = [
            Entity\CustomerProduct::class => [
                'name' => 'api_customers.me_product',
                'params' => ['id' => 'object.id', '_locale' => 'request._locale'],
            ],
            Entity\Customer::class => [
                'name' => 'api_customers.me',
                'params' => ['_locale' => 'request._locale'],
                'linkMethod' => 'customerLinks',
            ],
            Entity\Transaction::class => [
                'name' => 'api_customers.me_transaction',
                'params' => ['_locale' => 'request._locale', 'id' => 'object.id'],
            ],
            Entity\Country::class => [
                'name' => 'api_countries.country',
                'params' => ['_locale' => 'request._locale', 'code' => 'object.code'],
            ],
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
                    $groups = array('DEFAULT');
                }

                break;
            }

            $groups = $groups[$path];
        }

        return $groups;
    }
}
