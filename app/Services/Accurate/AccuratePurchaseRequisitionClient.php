<?php

namespace App\Services\Accurate;

final class AccuratePurchaseRequisitionClient
{
    public function __construct(
        private AccurateHostDiscovery $hostDiscovery,
        private AccurateHttpClient $http,
    ) {}

    public function listByNumberContains(string $term, int $limit = 5): array
    {
        $host = $this->hostDiscovery->getHost();

        return $this->http->get($host.'/api/purchase-requisition/list.do', [
            'filter.number.op' => 'CONTAIN',
            'filter.number.val' => $term,
            'sp.page' => 1,
            'sp.pageSize' => $limit,
        ]);
    }

    public function detailById(string|int $id): array
    {
        $host = $this->hostDiscovery->getHost();

        return $this->http->get($host.'/api/purchase-requisition/detail.do', [
            'id' => $id,
        ]);
    }

    public function detailByNumber(string $number): array
    {
        $host = $this->hostDiscovery->getHost();

        return $this->http->get($host.'/api/purchase-requisition/detail.do', [
            'number' => $number,
        ]);
    }
}

