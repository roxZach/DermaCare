<div class="card"><div class="card-header">Appointments</div><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
    @forelse($appointments as $a)
        <tr><td>{{ substr($a->time, 0, 5) }}</td><td><a href="{{ route('reception.patients.show', $a->patient) }}">{{ $a->patient->name }}</a></td><td>{{ $a->doctor->name }}</td>
            <td>@include('partials.status', ['s' => $a->status]) @if($a->queue_no)<span class="small">#{{ $a->queue_no }}</span>@endif</td>
            <td class="text-end text-nowrap">
                @if($a->status === 'booked')
                    @if($a->date->isToday())<form method="POST" action="{{ route('reception.appointments.status', $a) }}" class="d-inline">@csrf<input type="hidden" name="status" value="checked_in"><button class="btn btn-sm btn-success">Check in</button></form>@endif
                    <a href="{{ route('reception.appointments.edit', $a) }}" class="btn btn-sm btn-outline-primary">Reschedule</a>
                    <form method="POST" action="{{ route('reception.appointments.status', $a) }}" class="d-inline" data-confirm="Cancel this appointment?">@csrf<input type="hidden" name="status" value="cancelled"><button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                @elseif($a->status === 'checked_in')
                    <form method="POST" action="{{ route('reception.appointments.status', $a) }}" class="d-inline">@csrf<input type="hidden" name="status" value="booked"><button class="btn btn-sm btn-outline-secondary">Undo check-in</button></form>
                @elseif($a->status === 'completed' && ! $a->invoice)
                    <a href="{{ route('reception.invoices.create', ['patient' => $a->patient_id, 'appointment' => $a->id]) }}" class="btn btn-sm btn-teal">Create invoice</a>
                @endif
            </td></tr>
    @empty <tr><td colspan="5" class="text-muted">No appointments.</td></tr> @endforelse
</tbody></table></div></div>
