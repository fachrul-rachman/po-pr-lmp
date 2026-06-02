@props([
    'status' => null,
])

@php
    $label = match ((string) $status) {
        'sesuai' => 'Sesuai',
        'tidak_sesuai' => 'Tidak Sesuai',
        default => $status ? ucfirst(str_replace('_', ' ', (string) $status)) : '-',
    };
@endphp

{{ $label }}

