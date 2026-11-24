{{--
    Body map (front and back). Parameters:
    $lesions   collection of lesions (with x, y, view, label)
    $clickable true = clicking places a marker and fills inputs #bm-x, #bm-y, #bm-view, #bm-region
    $linkTo    optional closure(lesion) => url
--}}
@php($clickable = $clickable ?? false)
<div class="row g-2 bodymap {{ $clickable ? 'bodymap-clickable' : '' }}">
    @foreach(['front' => 'Front', 'back' => 'Back'] as $view => $title)
        <div class="col-6 text-center">
            <div class="small text-muted mb-1">{{ $title }}</div>
            <svg viewBox="0 0 200 440" class="bm-svg" data-view="{{ $view }}" role="img" aria-label="{{ $title }} body diagram">
                <g class="bm-body">
                    <circle cx="100" cy="38" r="27"/>
                    <rect x="88" y="62" width="24" height="18" rx="4"/>
                    <rect x="58" y="78" width="84" height="150" rx="22"/>
                    <rect x="30" y="84" width="26" height="150" rx="12"/>
                    <rect x="144" y="84" width="26" height="150" rx="12"/>
                    <rect x="62" y="220" width="36" height="205" rx="14"/>
                    <rect x="102" y="220" width="36" height="205" rx="14"/>
                </g>
                @if($view === 'back')<line x1="100" y1="84" x2="100" y2="220" class="bm-spine"/>@endif
                @foreach(($lesions ?? collect())->where('view', $view) as $l)
                    @php($url = isset($linkTo) ? $linkTo($l) : null)
                    @if($url)<a href="{{ $url }}">@endif
                    <g class="bm-marker">
                        <circle cx="{{ $l->x * 2 }}" cy="{{ $l->y * 4.4 }}" r="7"><title>{{ $l->label }} ({{ $l->body_region }})</title></circle>
                        <text x="{{ $l->x * 2 }}" y="{{ $l->y * 4.4 + 3 }}" text-anchor="middle">{{ $loop->iteration }}</text>
                    </g>
                    @if($url)</a>@endif
                @endforeach
                <circle class="bm-new d-none" r="7"/>
            </svg>
        </div>
    @endforeach
</div>
@if($clickable)
    <div class="small text-muted mt-1"><i class="bi bi-hand-index"></i> Click on the body diagram to mark where the lesion is.
        <span id="bm-picked" class="fw-semibold text-dark"></span></div>
@endif
