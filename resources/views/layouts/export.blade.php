<!doctype html>
<html lang="{{ $locale->htmlLang() }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title')</title>

    @if($cspContent ?? false)
        <meta http-equiv="Content-Security-Policy" content="{{ $cspContent }}">
    @endif

    @include('exports.parts.styles', ['format' => $format, 'engine' => $engine ?? ''])
    @include('exports.parts.custom-head')
</head>
<body class="export export-format-{{ $format }} export-engine-{{ $engine ?? 'none' }}">
@include('layouts.parts.export-body-start')
@yield('topbar')
<div class="page-content" dir="auto">
    @yield('content')
</div>
<div class="export-footer">
    <div class="export-footer-inner">
        @if(($exportBranding['brac_logo'] ?? null) || ($exportBranding['three_devs_logo'] ?? null))
            <div class="export-footer-logos">
                @if($exportBranding['brac_logo'] ?? null)
                    <img src="./{{ $exportBranding['brac_logo'] }}" alt="BRAC" class="export-footer-logo export-footer-logo-brac" loading="eager">
                @endif
                @if($exportBranding['three_devs_logo'] ?? null)
                    <img src="./{{ $exportBranding['three_devs_logo'] }}" alt="3DEVS" class="export-footer-logo export-footer-logo-3devs" loading="eager">
                @endif
            </div>
        @endif
    </div>
</div>
@yield('scripts')
@include('layouts.parts.export-body-end')
</body>
</html>
