<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>@yield('title') · DermaCare AI</title>
<link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
<style>body{background:#fff}.sheet{max-width:820px;margin:24px auto;padding:32px;border:1px solid #e3e8ee}.brand{color:var(--dc-navy);font-weight:700;font-size:1.4rem}
@media print{.sheet{border:0;margin:0;padding:0}}</style></head>
<body>
<div class="text-center my-3 no-print"><button onclick="window.print()" class="btn btn-primary">Print / Save as PDF</button></div>
<div class="sheet">
    <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
        <div><div class="brand">DermaCare AI</div><div class="small text-muted">Dermatology Hospital · {{ config('app.url') }}</div></div>
        <div class="text-end small">@yield('doc-meta')</div>
    </div>
    @yield('body')
</div>
</body></html>
