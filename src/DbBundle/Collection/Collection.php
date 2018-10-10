<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DbBundle\Collection;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Description of AbstractCollection
 *
 * @author cnonog
 */
class Collection
{
    private $total;
    private $totalFiltered;
    private $limit;
    private $page;
    private $items;

    public function __construct($elements, int $total = 0, int $totalFiltered = 0, int $limit = 10, int $page = 1)
    {
        if ($elements instanceof ArrayCollection) {
            $this->items = $elements;
        } elseif (is_array($elements)) {
            $this->items = new ArrayCollection($elements);
        } else {
            $this->items = $elements;
        }

        $this->total = $total;
        $this->totalFiltered = $totalFiltered;
        $this->limit = $limit;
        $this->page = $page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getTotalFiltered(): int
    {
        return $this->totalFiltered;
    }

    public function getItems()
    {
        return $this->items;
    }
}
