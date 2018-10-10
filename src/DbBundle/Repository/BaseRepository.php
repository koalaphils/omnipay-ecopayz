<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DbBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BaseRepository extends EntityRepository
{
    public function save($entity)
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function beginTransaction()
    {
        $this->getEntityManager()->beginTransaction();
    }

    public function commit()
    {
        $this->getEntityManager()->commit();
    }

    public function rollback()
    {
        $this->getEntityManager()->rollback();
    }
    
    public function reconnectToDatabase(): void
    {
        $this->getEntityManager()->getConnection()->reconnect();
    }

    public function getEntityManager()
    {
        return parent::getEntityManager();
    }

    public function getAliases($reverse = false)
    {
        return [];
    }

    public function getAvailableFilters()
    {
        return [];
    }

    public function getMainId()
    {
        $main = $this->getAliases(true)['_main_'];

        return $main . '.' . $this->getAliases()[$main]['i'];
    }

    /**
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string                     $alias
     * @param array                      $aliases
     * @param array                      $selects
     *
     * @return string
     */
    protected function getPartials($qb, $alias, &$aliases, $selects = null)
    {
        $partials = [];
        $this->partial($qb, $alias, $aliases, $selects, $partials);
        foreach ($partials as $key => &$partial) {
            if (is_array($partial)) {
                $partial = "PARTIAL $key.{" . implode(', ', $partial) . '}';
            } else {
                $partial = $partial;
            }
        }

        return implode(', ', $partials);
    }

    final protected function join($qb, $alias, &$aliases)
    {
        $_a = array_get($aliases, $alias, []);
        if (empty($_a)
            || array_get($_a, 'main', false)
            || array_get($_a, 'added', false)
            || (is_string($_a) && $_a === '_main_')
        ) {
            return;
        }
        if ($_a !== null && array_has($aliases, $_a['p'])) {
            $this->join($qb, $_a['p'], $aliases);
        }
        switch (array_get($_a, 'type', '')) {
            case 'left':
                $qb->leftJoin($_a['p'] . '.' . $_a['c'], $alias);
                break;
            case 'inner':
                $qb->innerJoin($_a['p'] . '.' . $_a['c'], $alias);
                break;
            default:
                $qb->join($_a['p'] . '.' . $_a['c'], $alias);
                break;
        }
        $aliases[$alias]['added'] = true;
    }

    final protected function partial($qb, $alias, &$aliases, $selects, &$partials)
    {
        if ($selects === null || (is_array($selects) && empty($selects))) {
            $partials[$alias] = $alias;

            return;
        }
        if (!is_array($selects)) {
            $selects = [$selects];
        }
        foreach ($selects as $key => $column) {
            if (is_string($column)) {
                $partials[$alias][] = "$column";
            } elseif (is_array($column)) {
                $this->join($qb, $key, $aliases);
                $this->partial($qb, $key, $aliases, $column, $partials);
            }
        }
        $identifier = array_get($aliases[$alias], 'i', 'id');
        if (!in_array($identifier, $partials[$alias])) {
            $partials[$alias][] = $identifier;
        }

        $mustColumns = array_get($aliases[$alias], 'must', []);
        foreach ($mustColumns as $column) {
            $partials[$alias][] = $column;
        }
    }
    
    protected function setToUnbuffered(): void
    {
        $this->getEntityManager()
            ->getConnection()
            ->getWrappedConnection()
            ->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    }
    
    protected function setToBuffered(): void
    {
        $this->getEntityManager()
            ->getConnection()
            ->getWrappedConnection()
            ->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }
}
