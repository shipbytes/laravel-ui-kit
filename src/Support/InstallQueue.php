<?php

namespace Shipbytes\UiKit\Support;

/**
 * Process-wide queue of tail commands accumulated during an install run.
 *
 * This must NOT live as static properties on the InstallsModule trait:
 * each class using a trait gets its own copy of the trait's statics, so
 * deferrals registered by ui-kit:install-module would be invisible to the
 * parent ui-kit:install that drains the queue.
 */
final class InstallQueue
{
    /** @var array<int, string> JSON-encoded vendor:publish argument arrays */
    public static array $vendorPublishes = [];

    /** @var array<int, string> */
    public static array $seeders = [];

    public static bool $storageLink = false;

    public static bool $migrate = false;

    public static function reset(): void
    {
        self::$vendorPublishes = [];
        self::$seeders = [];
        self::$storageLink = false;
        self::$migrate = false;
    }
}
