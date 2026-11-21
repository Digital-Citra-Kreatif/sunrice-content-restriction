{{--
    A restricted entry or term page, for a visitor who may not open it. The page's own variables
    ($page, $entry or $term, $title) and $signedIn; for a guest, $signInUrl (back here once signed
    in) and $registerUrl (null when visitors can't create an account). Replace it with
    resources/views/vendor/content-restriction/restricted.blade.php.
--}}
@extends(\Sunrice\Account\View\Layout::site())
@include(\Sunrice\Account\View\Layout::view('partials.push-styles'))

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <section class="account-auth content-restricted">
        <h1 class="account-auth__title">{{ $title }}</h1>
        @if ($signedIn)
            <p>{{ __('content-restriction::restriction.no_access') }}</p>
        @else
            <p>{{ __('content-restriction::restriction.sign_in_to_read') }}</p>
            <p class="account-actions content-restricted__actions">
                <a href="{{ $signInUrl }}" class="account-button account-button--primary">{{ __('content-restriction::restriction.sign_in') }}</a>
                @if ($registerUrl)
                    <a href="{{ $registerUrl }}">{{ __('content-restriction::restriction.register') }}</a>
                @endif
            </p>
        @endif
    </section>
@endsection
