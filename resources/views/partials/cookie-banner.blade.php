{{--
    Styles are inline for historical reasons: this was included by two layouts
    and only one of them loaded site.css. Since the parked marketing layout was
    deleted, layouts.site is the only includer and these could move into the
    stylesheet — no longer a constraint, just not yet tidied.

    Sticky rather than fixed: as the last element in the flow it reserves its
    own height, so it can never cover the footer the way a fixed overlay did,
    while still staying pinned to the bottom of the viewport when the page is
    long enough to scroll.
--}}
@if (!request()->cookie('cookie_consent'))
    <div id="cookie-banner"
        style="position:sticky;bottom:0;z-index:9999;background:#0A0F24;color:#ffffff;padding:14px 20px;font-family:'Inter',sans-serif;font-size:13px;display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:14px">
        <span style="flex:1;min-width:220px;max-width:680px;line-height:1.5">
            We use essential cookies to run the site and Google Analytics to understand how it is used.
            See our <a href="{{ url('/privacy-policy') }}" style="color:#D4EBF2">Privacy Policy</a>.
        </span>
        <span style="display:flex;gap:10px;flex-shrink:0">
            <a href="{{ route('cookies.accept') }}"
                style="background:#348FAD;border:1.5px solid #348FAD;color:#ffffff;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap">Accept</a>
            <a href="{{ route('cookies.reject') }}"
                style="background:transparent;border:1.5px solid rgba(255,255,255,0.25);color:#ffffff;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap">Decline</a>
        </span>
    </div>
@endif
