<?php

namespace App\Support;

use Closure;

/**
 * Where a logged change came from. It becomes the activity's log_name, so the activity log (and future statistics)
 * can tell an admin's edit from an approved public submission, an import or a background job.
 *
 * Code that knows its source says so with ActivitySource::as(); otherwise it's worked out from who's logged in.
 */
final class ActivitySource
{
    public const string ADMIN = 'admin';

    public const string PUBLIC_SUBMISSION = 'public-submission';

    public const string IMPORT = 'import';

    /** queue jobs and artisan commands with nobody logged in, e.g. geocoding or zed:* maintenance */
    public const string SYSTEM = 'system';

    /** a public web request, e.g. someone registering an account */
    public const string WEB = 'web';

    private static ?string $current = null;

    /** @var array<string, mixed> */
    private static array $properties = [];

    public static function current(): string
    {
        if (null !== self::$current) {
            return self::$current;
        }

        if (backpack_auth()->check()) {
            return self::ADMIN;
        }

        return app()->runningInConsole() ? self::SYSTEM : self::WEB;
    }

    /**
     * Extra properties for every entry logged inside as(), e.g. which public submission was approved.
     *
     * @return array<string, mixed>
     */
    public static function properties(): array
    {
        return self::$properties;
    }

    /**
     * Run $callback with every change it logs labelled $source, and carrying $properties.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @param  array<string, mixed>  $properties
     * @return T
     */
    public static function as(string $source, Closure $callback, array $properties = []): mixed
    {
        [$previousSource, $previousProperties] = [self::$current, self::$properties];
        self::$current = $source;
        self::$properties = [...$previousProperties, ...$properties];

        try {
            return $callback();
        } finally {
            [self::$current, self::$properties] = [$previousSource, $previousProperties];
        }
    }
}
