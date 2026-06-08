<?php

namespace App\Services\Accurate;

final class AccuratePurchaseRequisitionClient
{
    public function __construct(
        private AccurateHostDiscovery $hostDiscovery,
        private AccurateHttpClient $http,
    ) {}

    public function listByNumberContains(string $term, int $limit = 5, ?string $company = null): array
    {
        $host = $this->hostDiscovery->getHost($company);

        return $this->http->get($host.'/api/purchase-requisition/list.do', [
            'filter.number.op' => 'CONTAIN',
            'filter.number.val' => $term,
            'sp.page' => 1,
            'sp.pageSize' => $limit,
        ], company: $company);
    }

    public function detailById(string|int $id, ?string $company = null): array
    {
        $host = $this->hostDiscovery->getHost($company);

        return $this->http->get($host.'/api/purchase-requisition/detail.do', [
            'id' => $id,
        ], company: $company);
    }

    public function detailByNumber(string $number, ?string $company = null): array
    {
        $host = $this->hostDiscovery->getHost($company);

        return $this->http->get($host.'/api/purchase-requisition/detail.do', [
            'number' => $number,
        ], company: $company);
    }
}
