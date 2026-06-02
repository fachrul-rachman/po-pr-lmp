@props([
    'role' => null,
])

@php
    $label = match ((string) $role) {
        'admin' => 'Admin',
        'warehouse' => 'Warehouse',
        'spv' => 'SPV',
        'finance' => 'Finance',
        'purchasing' => 'Purchasing',
        'system' => 'System',
        default => $role ? ucfirst(str_replace('_', ' ', (string) $role)) : '-',
    };
@endphp

{{ $label }}

