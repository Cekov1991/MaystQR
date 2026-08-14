@props(['url'])
{{--
    Laravel's stock header prints the app name as plain text, and swaps in
    Laravel's own logo only when that name is literally "Laravel". We always
    show our own mark.

    The src must be absolute and publicly reachable, because the mail client
    fetches it from outside the application — so this depends on APP_URL being
    correct in every environment that sends mail. The alt text carries the brand
    name for the many clients that block remote images by default.

    Width and height are set as HTML attributes as well as CSS: Outlook ignores
    the stylesheet here and would otherwise render the image at its full 590px.
--}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('images/easy-qr-logo-trim.png') }}" class="logo" width="63" height="56" alt="{{ config('app.name') }}">
</a>
</td>
</tr>
