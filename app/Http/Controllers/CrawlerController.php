<?php

namespace App\Http\Controllers;

use App\Support\PublicPages;
use Illuminate\Http\Response;

/**
 * The three files nobody visits and every crawler fetches first.
 *
 * Served from routes rather than sitting in public/ so that every URL inside
 * them is built by the router. A static public/robots.txt has to hardcode the
 * domain in its Sitemap line, which is wrong the moment it is read on staging,
 * and a static sitemap has to be edited by hand whenever a route moves.
 *
 * public/robots.txt was deleted when this was added. Had it stayed, the web
 * server would have served the file and never reached this route, and the rules
 * below would have been dead code that tested green.
 */
class CrawlerController extends Controller
{
    public function robots(): Response
    {
        return $this->text(view('crawlers.robots'));
    }

    public function sitemap(): Response
    {
        return $this->text(view('crawlers.sitemap', [
            'pages' => PublicPages::all(),
            'declaration' => '<?xml version="1.0" encoding="UTF-8"?>',
        ]), 'application/xml');
    }

    /**
     * A proposed convention for handing a language model a plain-text map of a
     * site instead of making it parse the HTML. No major model provider has
     * confirmed reading it, so this is a cheap bet rather than a load-bearing
     * one — it costs a view and earns nothing if the convention dies.
     */
    public function llms(): Response
    {
        return $this->text(view('crawlers.llms', ['pages' => PublicPages::all()]));
    }

    private function text(mixed $view, string $contentType = 'text/plain'): Response
    {
        // Trimmed because Blade leaves the newlines from its own comments and
        // directives in the output, and a robots.txt that opens with blank lines
        // is a file some parsers stop reading.
        return response(trim($view->render())."\n", 200, ['Content-Type' => $contentType.'; charset=UTF-8']);
    }
}
