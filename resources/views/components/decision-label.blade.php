@props([
    'type' => null,
])

@php
    $label = match ((string) $type) {
        'warehouse_submit' => 'Warehouse Submit',
        'warehouse_resubmit' => 'Warehouse Re-Submit',
        'spv_approve' => 'SPV Approve',
        'spv_reject' => 'SPV Reject',
        'finance_close' => 'Finance Close',
        'finance_reject' => 'Finance Reject',
        'admin_override' => 'Admin Override',
        'accurate_refresh' => 'Refresh Accurate',
        'system_status_change' => 'Perubahan Status Sistem',
        default => $type ? ucwords(str_replace('_', ' ', (string) $type)) : '-',
    };
@endphp

{{ $label }}

