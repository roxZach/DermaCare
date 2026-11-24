<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DermaCare AI') · DermaCare AI</title>
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('head')
</head>
<body>
@php($u = auth()->user())
<nav class="navbar navbar-expand-lg navbar-dark dc-nav sticky-top">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand fw-semibold" href="{{ $u ? route('dashboard') : route('home') }}">
            <i class="bi bi-shield-plus"></i> DermaCare <span class="dc-ai">AI</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                @guest
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('services') }}">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('doctors') }}">Dermatologists</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('articles') }}">Skin-care articles</a></li>
                @else
                    @switch($u->role)
                        @case('patient')
                            <li class="nav-item"><a class="nav-link" href="{{ route('patient.dashboard') }}">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('patient.appointments.index') }}">Appointments</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('patient.tele.index') }}">Teledermatology</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('patient.lesions') }}">My lesions</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('patient.reports') }}">Reports</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('patient.history') }}">History</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('patient.bills.index') }}">Bills</a></li>
                            @break
                        @case('dermatologist')
                            <li class="nav-item"><a class="nav-link" href="{{ route('doctor.dashboard') }}">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('doctor.schedule') }}">Schedule</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('doctor.patients') }}">Patients</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('doctor.analyses.pending') }}">AI reviews</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('doctor.tele.index') }}">Teledermatology</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('doctor.biopsy.index') }}">Biopsies</a></li>
                            @break
                        @case('receptionist')
                            <li class="nav-item"><a class="nav-link" href="{{ route('reception.dashboard') }}">Front desk</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('reception.patients') }}">Patients</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('reception.appointments') }}">Appointments</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('reception.invoices.index') }}">Invoices</a></li>
                            @break
                        @case('lab')
                            <li class="nav-item"><a class="nav-link" href="{{ route('lab.dashboard') }}">Pending orders</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('lab.history') }}">Report history</a></li>
                            @break
                        @case('admin')
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}">Analytics</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">Users</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Clinic</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('admin.doctors.index') }}">Doctors &amp; schedules</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.services.index') }}">Services &amp; fees</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.articles.index') }}">Articles</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">AI &amp; chatbot</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('admin.ai.index') }}">AI model versions</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.ai.performance') }}">AI performance</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.faqs.index') }}">FAQ knowledge base</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.faqs.unanswered') }}">Unanswered questions</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">System</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('admin.audit') }}">Audit logs</a></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.backup') }}">Backup &amp; restore</a></li>
                                </ul>
                            </li>
                            @break
                    @endswitch
                @endguest
            </ul>
            <ul class="navbar-nav align-items-lg-center">
                @guest
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Log in</a></li>
                    <li class="nav-item"><a class="btn btn-light btn-sm ms-lg-2" href="{{ route('register') }}">Register</a></li>
                @else
                    @php($unread = $u->alerts()->whereNull('read_at')->count())
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" title="Notifications">
                            <i class="bi bi-bell fs-5"></i>
                            @if($unread)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $unread }}</span>@endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dc-alerts p-0">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <strong>Notifications</strong>
                                <form method="POST" action="{{ route('alerts.readAll') }}">@csrf<button class="btn btn-link btn-sm p-0">Mark all read</button></form>
                            </div>
                            @forelse($u->alerts()->take(6)->get() as $a)
                                <a class="dropdown-item small py-2 {{ $a->read_at ? '' : 'fw-semibold' }}" href="{{ route('alerts.open', $a) }}">
                                    {{ $a->title }}<br><span class="text-muted fw-normal">{{ \Illuminate\Support\Str::limit($a->message, 60) }}</span>
                                </a>
                            @empty
                                <div class="p-3 small text-muted">No notifications yet.</div>
                            @endforelse
                            <a class="dropdown-item text-center small border-top py-2" href="{{ route('alerts.index') }}">View all</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> {{ $u->name }} <span class="badge text-bg-light ms-1">{{ $u->role_label }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if($u->hasRole('patient'))
                                <li><a class="dropdown-item" href="{{ route('patient.profile') }}">My profile</a></li>
                                <li><a class="dropdown-item" href="{{ route('patient.chat.history') }}">Chat history</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('home') }}">Public website</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item">Log out</button></form></li>
                        </ul>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

<main class="@yield('container', 'container') py-4">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="dc-footer py-4 mt-5">
    <div class="container small d-flex flex-wrap justify-content-between gap-2">
        <span>&copy; {{ date('Y') }} DermaCare AI · Dermatology Hospital Management System</span>
        <span><i class="bi bi-info-circle"></i> AI results are decision support only and are always reviewed by a dermatologist.</span>
    </div>
</footer>

@if(!$u || $u->hasRole('patient'))
    @include('partials.chatbot')
@endif

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script>$.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}});</script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
