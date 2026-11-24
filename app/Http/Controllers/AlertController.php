<?php

namespace App\Http\Controllers;

use App\Models\Alert;

/** Use case: Receive Notifications (in-app). */
class AlertController extends Controller
{
    public function index()
    {
        return view('shared.alerts', ['alerts' => auth()->user()->alerts()->paginate(20)]);
    }

    public function open(Alert $alert)
    {
        abort_unless($alert->user_id === auth()->id(), 403);
        $alert->update(['read_at' => $alert->read_at ?? now()]);
        return redirect($alert->link ?: route('alerts.index'));
    }

    public function readAll()
    {
        auth()->user()->alerts()->whereNull('read_at')->update(['read_at' => now()]);
        return back();
    }
}
