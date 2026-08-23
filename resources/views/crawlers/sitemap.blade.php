{{--
    Deliberately bare. <changefreq> and <priority> are ignored by every major
    crawler, and <lastmod> would have to be invented here because nothing records
    when a page's content last meaningfully changed. A sitemap that states things
    it does not know is worth less than one that states only the URLs.

    The XML declaration arrives as $declaration rather than being written here.
    short_open_tag is on in this environment, so a literal "<?xml" in a Blade
    file is read as an opening PHP tag and the rest of the line is compiled as
    code — which is exactly how this first failed.
--}}
{!! $declaration !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($pages as $page)
    <url>
        <loc>{{ $page['url'] }}</loc>
    </url>
@endforeach
</urlset>
