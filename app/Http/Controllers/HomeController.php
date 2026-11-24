<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Doctor;
use App\Models\Service;
use Illuminate\Http\Request;

/** Public pages that Guests can open without logging in. */
class HomeController extends Controller
{
    public function home()
    {
        return view('public.home', [
            'services' => Service::where('is_active', true)->take(6)->get(),
            'doctors' => Doctor::with('user')->whereHas('user', fn ($q) => $q->where('is_active', true))->take(4)->get(),
            'articles' => Article::where('is_published', true)->latest()->take(3)->get(),
        ]);
    }

    public function services()
    {
        $services = Service::where('is_active', true)->orderBy('category')->orderBy('name')->get()->groupBy('category');
        return view('public.services', compact('services'));
    }

    // Use case: Search & View Dermatologists
    public function doctors(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $doctors = Doctor::with('user', 'reviews')
            ->whereHas('user', fn ($u) => $u->where('is_active', true))
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('specialty', 'like', "%$q%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$q%"));
            }))->get();
        $specialties = Doctor::distinct()->pluck('specialty');
        return view('public.doctors', compact('doctors', 'q', 'specialties'));
    }

    // Use case: View profile + <<extend>> Check Doctor Availability
    public function doctor(Doctor $doctor)
    {
        $doctor->load('user', 'schedules', 'reviews.patient');
        return view('public.doctor', compact('doctor'));
    }

    /** JSON list of free slots, used by the booking forms and the availability checker. */
    public function slots(Request $request, Doctor $doctor)
    {
        $date = $request->validate(['date' => 'required|date'])['date'];
        return response()->json(['date' => $date, 'slots' => $doctor->availableSlots($date)]);
    }

    public function articles()
    {
        return view('public.articles', ['articles' => Article::where('is_published', true)->latest()->paginate(9)]);
    }

    public function article(Article $article)
    {
        abort_unless($article->is_published, 404);
        return view('public.article', compact('article'));
    }
}
