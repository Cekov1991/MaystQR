{{--
    Served from a route, not from public/robots.txt, so the Sitemap line below
    carries whatever domain the app is actually running on.

    Two things are being said here, and they are not the same thing:

      - Retrieval crawlers fetch a page at the moment someone asks an assistant
        a question, and what they fetch is what gets quoted back with a link to
        us. Blocking these is what makes a site invisible in answers.

      - Training crawlers take content into a model, which is how a model comes
        to recommend a product without browsing for it. This is the slower and
        larger half, and unlike the first it cannot be taken back.

    Both are allowed. The trade would be worth arguing about if these pages held
    anything we sold; they hold marketing copy and legal notices whose whole
    purpose is to be read and repeated.
--}}
@php($closed = \App\Support\PublicPages::closedPaths())
Sitemap: {{ url('/sitemap.xml') }}

# Everyone else.
User-agent: *
@foreach ($closed as $path)
Disallow: {{ $path }}
@endforeach

# Retrieval and citation. These decide whether we appear in an answer today.
User-agent: OAI-SearchBot
User-agent: ChatGPT-User
User-agent: Claude-SearchBot
User-agent: Claude-User
User-agent: PerplexityBot
User-agent: Perplexity-User
User-agent: Amazonbot
User-agent: Applebot
Allow: /
@foreach ($closed as $path)
Disallow: {{ $path }}
@endforeach

# Training. These decide whether a model knows us without looking.
User-agent: GPTBot
User-agent: ClaudeBot
User-agent: Google-Extended
User-agent: Applebot-Extended
User-agent: CCBot
User-agent: meta-externalagent
User-agent: cohere-training-data-crawler
Allow: /
@foreach ($closed as $path)
Disallow: {{ $path }}
@endforeach
