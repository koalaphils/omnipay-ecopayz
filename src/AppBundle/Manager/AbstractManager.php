<?php

namespace AppBundle\Manager;

use Symfony\Component\EventDispatcher\Event;

abstract class AbstractManager
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;
    use \AppBundle\Traits\UserAwareTrait;

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get Doctrine.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    public function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    public function getEntityManager($name = 'default'): \Doctrine\ORM\EntityManager
    {
        return $this->getDoctrine()->getManager($name);
    }

    protected function dispatchEvent(string $eventName, Event $event): void
    {
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->dispatch($eventName, $event);
    }
    
    /**
     * Get Router.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    public function getRouter()
    {
        return $this->getContainer()->get('router');
    }

    public function save($entity)
    {
        //return $this->getRepository()->save($entity);
        $this->getDoctrine()->getManager()->persist($entity);
        $this->getDoctrine()->getManager()->flush($entity);
    }

    public function remove($entity)
    {
        $this->getDoctrine()->getManager()->remove($entity);
    }

    public function beginTransaction()
    {
        $this->getDoctrine()->getManager()->beginTransaction();
    }

    public function commit()
    {
        $this->getDoctrine()->getManager()->commit();
    }

    public function rollback()
    {
        $this->getDoctrine()->getManager()->rollback();
    }

    /**
     * Get a user from the Security Token Storage.
     *
     * @return mixed
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    public function getUser()
    {
        return $this->_getUser();
    }

    /**
     * Get Errors
     *
     * @param \Symfony\Component\Form\Form $form
     * @param array                        $errors
     *
     * @return array
     */
    public function getErrorMessages($form)
    {
        $errors = [];
        foreach ($form->getErrors() as $key => $error) {
            $errors[$key] = $error->getMessage();
        }
        foreach ($form as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    public function getAlias($name = null)
    {
        $aliases = $this->getRepository()->getAliases(true);
        if ($name === null) {
            return $aliases;
        }
        $info = array_get($aliases, $name, false);
        if ($info === false) {
            throw new \Exception(sprintf('Name "%s" does not exist in alias', $name));
        }
        if (is_string($info)) {
            return $info;
        }

        return $info['a'];
    }

    protected function getColumn($obj, $column)
    {
        $_column = explode('.', $column);
        if (count($_column) === 2) {
            list($name, $column) = $_column;
            if ($name === 'dwl') {
                $name = '_main_';
            }
        } elseif (count($_column) > 2) {
            $column = $_column[count($_column) - 1];
            $name = implode('.', array_slice($_column, 0, -1));
        } else {
            $column = $_column[0];
            $name = '_main_';
        }
        $alias = $obj->getAlias($name);

        return $alias . '.' . $column;
    }

    /**
     * @param type $options
     *
     * @return array Returns array that consist 3 item the filter, order, select
     */
    protected function processOption($options)
    {
        $orders = [];
        $filters = [];
        $select = [];
        $result = ['data' => [], 'filtered' => 0, 'total' => 0];

        foreach (array_get($options, 'order', []) as $key => $order) {
            $column = $this->getColumn($this, $order['column']);
            $dir = array_get($order, 'dir', 'asc');
            $orders[] = "$column $dir";
        }
        foreach ($this->getRepository()->getAvailableFilters() as $filter) {
            if (array_has($options, $filter)) {
                $filters[$filter] = $options[$filter];
            }
        }
        foreach (array_get($options, 'column', []) as $column) {
            if (is_string($column)) {
                list($alias, $col) = explode('.', $this->getColumn($this, $column));
            } elseif (is_array($column)) {
                list($alias, $col) = explode('.', $this->getColumn($this, $column['data']));
            }
            if (!array_has($select, $alias)) {
                $select[$alias] = [];
            }
            if (!in_array($col, $select[$alias])) {
                $select[$alias][] = $col;
            }
        }

        if (empty($orders)) {
            $orders[] = $this->getRepository()->getMainId() . " asc";
        }

        return [$filters, $orders, $select];
    }

    protected function getSecurityTokenStorage()
    {
        return $this->getContainer()->get('security.token_storage');
    }

    protected function hasSecurityTokenStorage()
    {
        return $this->getContainer()->has('security.token_storage');
    }

    protected function get($id)
    {
        return $this->getContainer()->get($id);
    }

    protected function getParameter($parameterName)
    {
        return $this->getContainer()->getParameter($parameterName);
    }

    protected function getFormFactory()
    {
        return $this->get('form.factory');
    }

    abstract protected function getRepository();

    protected function getTranslator()
    {
        return $this->container->get('translator');
    }

    protected function isUnderTestEnvironment(): bool
    {
        return $this->get('kernel')->getEnvironment() === 'test';
    }
}
