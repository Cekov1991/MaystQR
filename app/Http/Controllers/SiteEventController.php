<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogSiteEventRequest;
use Illuminate\Http\Response;

/**
 * The half of the funnel that only a browser can see.
 *
 * Four of the counted events leave no trace on the server. A download of the
 * static code is a click on a data: URI that never reaches us; the offer being
 * shown, dismissed or clicked all happen inside one already-rendered page. There
 * is no request to hang them off, so the page has to report them.
 *
 * That makes this the one place where a stranger can cause a row to be written,
 * and everything defensive about the design lives next door in
 * LogSiteEventRequest rather than here. What is left is a validated insert and an
 * empty response.
 *
 * No body comes back on purpose. The caller is fire-and-forget script that
 * ignores the result, and a response with content in it would only invite
 * someone to start depending on one.
 */
class SiteEventController extends Controller
{
    public function store(LogSiteEventRequest $request): Response
    {
        $request->event()->record(context: $request->context());

        return response()->noContent();
    }
}
