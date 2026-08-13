@if (!request()->cookie('cookie_consent'))
    <div id="cookie-banner"
        style="position: fixed; bottom: 0; left: 0; right: 0; background: #222; color: #fff; padding: 10px 15px; font-size: 12px; display: flex; justify-content: space-between; align-items: center; z-index: 9999; flex-wrap: wrap; gap: 8px;">
        <span style="flex: 1; min-width: 200px;">
            We use essential cookies for site functionality and Google Analytics to improve your experience.
            See our <a href="{{ url('/privacy-policy') }}" style="color: #4da3ff;">Privacy Policy</a>.
        </span>
        <div style="display: flex; gap: 8px; flex-shrink: 0;">
            <a href="{{route('cookies.accept')}}"
                style="background: #4caf50; color: white; border: none; padding: 6px 12px; border-radius: 3px; text-decoration: none; cursor: pointer; white-space: nowrap; font-size: 12px;">Accept</a>
            <a href="{{route('cookies.reject')}}"
                style="background: #f44336; color: white; border: none; padding: 6px 12px; border-radius: 3px; text-decoration: none; cursor: pointer; white-space: nowrap; font-size: 12px;">Decline</a>
        </div>
    </div>
@endif
