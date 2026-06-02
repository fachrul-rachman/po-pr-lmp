@props([
    'status' => null,
])

@php
    $label = match ((string) $status) {
        'warehouse_submitted' => 'Menunggu SPV',
        'spv_approved' => 'Menunggu Finance',
        'spv_rejected' => 'Non Valid',
        'finance_rejected' => 'Non Close',
        'finance_closed' => 'Closed',
        default => $status ? ucfirst(str_replace('_', ' ', (string) $status)) : '-',
    };
@endphp

{{ $label }}

