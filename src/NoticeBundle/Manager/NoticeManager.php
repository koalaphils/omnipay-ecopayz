<?php

namespace NoticeBundle\Manager;

use AppBundle\Manager\AbstractManager;

/**
 * Description of NoticeManager.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class NoticeManager extends AbstractManager
{
    /**
     * @param type $filters
     *
     * @return type
     */
    public function getNoticeList($filters = null)
    {
        $results = [];

        if (array_get($filters, 'datatable', 0)) {
            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }

            $results['data'] = $this->getRepository()->getNoticeList($filters);
            if (array_get($filters, 'route', 0)) {
                $results['data'] = array_map(function ($result) {
                    $data = [];
                    $data['notice'] = $result;
                    $data['routes'] = [
                        'update' => $this->getRouter()->generate('notice.update_page', ['id' => $data['notice']['id']]),
                        'view' => $this->getRouter()->generate('notice.view_page', ['id' => $data['notice']['id']]),
                    ];

                    return $data;
                }, $results['data']);
            }
            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getNoticeListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getNoticeListAllCount();
        } elseif (array_get($filters, 'select2', 0)) {
            $results['items'] = array_map(function ($group) {
                return [
                    'id' => $group['id'],
                    'text' => $group['title'],
                ];
            }, $this->getRepository()->getNoticeList($filters));
            $results['recordsFiltered'] = $this->getRepository()->getNoticeListFilterCount($filters);
        } else {
            $results = $this->getRepository()->getNoticeList($filters);
        }

        return $results;
    }

    /**
     * Get notice repository.
     *
     * @return \DbBundle\Repository\NoticeRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Notice');
    }
}
