<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAbuseReportRequest;
use App\Notifications\AbuseReported;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

/**
 * The public channel for reporting a QR code that leads somewhere harmful.
 *
 * A dynamic QR code service is structurally a link shortener with a printed front
 * end, which makes it a known phishing vector. Terms section 4 has always
 * prohibited that use, but until now there was no way for anyone to tell us it was
 * happening — and a prohibition with no reporting channel is one we cannot
 * enforce.
 */
class AbuseReportController extends Controller
{
    public function create(): View
    {
        return view('report');
    }

    public function store(StoreAbuseReportRequest $request): RedirectResponse
    {
        Notification::route('mail', config('site.support_email'))
            ->notify(new AbuseReported($request->validated()));

        return redirect()
            ->route('report.create')
            ->with('status', 'report-received');
    }
}
