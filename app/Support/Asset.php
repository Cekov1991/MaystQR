<?php

namespace App\Support;

/**
 * URLs for files in public/ that change the URL when the file changes.
 *
 * public/css/site.css is served straight off disk with far-future caching and no
 * version in its URL, which meant a style change reached nobody who had already
 * visited. Not a theoretical problem: it cost two rounds of "it still looks
 * wrong" during a review, and the failure is silent — the deploy succeeds, the
 * file on disk is correct, and returning visitors keep the old stylesheet until
 * their cache expires on its own.
 *
 * A content hash rather than filemtime, deliberately. Modification times come
 * from whenever each instance checked the code out, so two servers behind a load
 * balancer can advertise different URLs for identical bytes — which turns one
 * cached file into two, and makes the version meaningless as a claim about
 * content. A hash is the same everywhere for the same file.
 *
 * Not Vite. site.css is hand-written and deliberately outside the bundler, which
 * is why it needs this at all; the Filament assets that do go through Vite are
 * already versioned by its manifest.
 */
class Asset
{
    /**
     * Hashes already computed this request, keyed by path.
     *
     * A page can reference the same file more than once, and hashing is a file
     * read. Once per request per file is enough — nothing rewrites these files
     * while the request is in flight.
     *
     * @var array<string, string|null>
     */
    private static array $versions = [];

    /**
     * The public URL for a file, with a short content hash appended.
     *
     * A path that does not resolve to a file on disk is returned unversioned
     * rather than throwing. The stylesheet going missing is already a visible
     * failure; a 500 on every page because a favicon was renamed is a worse one.
     */
    public static function versioned(string $path): string
    {
        $url = asset($path);

        $version = self::version($path);

        if ($version === null) {
            return $url;
        }

        return $url.'?v='.$version;
    }

    private static function version(string $path): ?string
    {
        if (! array_key_exists($path, self::$versions)) {
            $file = public_path($path);

            self::$versions[$path] = is_file($file)
                ? substr((string) md5_file($file), 0, 8)
                : null;
        }

        return self::$versions[$path];
    }

    /**
     * Drops the memoised hashes. For tests that write a file and read it back;
     * within one request the caching above is the point.
     */
    public static function forgetVersions(): void
    {
        self::$versions = [];
    }
}
