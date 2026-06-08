<?php

namespace App\Services\Accurate;

final class AccuratePurchaseOrderClient
{
    public function __construct(
        private AccurateHostDiscovery $hostDiscovery,
        private AccurateHttpClient $http,
    ) {}

    public function listByNumberContains(string $term, int $limit = 5, ?string $company = null): array
    {
        $host = $this->hostDiscovery->getHost($company);

        return $this->http->get($host.'/api/purchase-order/list.do', [
            'filter.number.op' => 'CONTAIN',
            'filter.number.val' => $term,
            'sp.page' => 1,
            'sp.pageSize' => $limit,
        ], company: $company);
    }

    public function detailById(string|int $id, ?string $company = null): array
    {
        $host = $this->hostDiscovery->getHost($company);

        return $this->http->get($host.'/api/purchase-order/detail.do', [
            'id' => $id,
        ], company: $company);
    }

    public function detailByNumber(string $number, ?string $company = null): array
    {
        $host = $this->hostDiscovery->getHost($company);

        return $this->http->get($host.'/api/purchase-order/detail.do', [
            'number' => $number,
        ], company: $company);
    }
}
