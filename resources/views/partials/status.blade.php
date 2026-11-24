@php
$map = [
    'booked' => 'primary', 'checked_in' => 'warning', 'completed' => 'success', 'cancelled' => 'secondary',
    'pending_review' => 'warning', 'confirmed' => 'success', 'overridden' => 'info', 'failed' => 'danger',
    'submitted' => 'warning', 'reviewed' => 'success', 'unpaid' => 'danger', 'paid' => 'success',
    'ordered' => 'secondary', 'sample_collected' => 'info', 'sent_to_lab' => 'info', 'in_process' => 'primary',
    'low' => 'success', 'medium' => 'warning', 'high' => 'danger',
];
@endphp
<span class="badge text-bg-{{ $map[$s] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $s ?? '-')) }}</span>
