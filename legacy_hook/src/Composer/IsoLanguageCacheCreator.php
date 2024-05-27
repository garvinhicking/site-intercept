<?php

declare(strict_types = 1);

namespace App\Composer;

use Composer\Script\Event;

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/**
 * The package sokil/php-isocodes-db-i18n provides a database for
 * countries and languages. This is also used by the TYPO3 Core.
 *
 * This helper scripts parses the installed JSON files and creates
 * a PHP-lookup cache file for easy reference.
 *
 * It is created via a composer script so that the file is created
 * only once on deployment (or local testing) and can not change
 * without a composer package update then. This way, no further
 * workflow actions are needed.
 */
readonly class IsoLanguageCacheCreator
{
    public const CACHE_DIR = __DIR__ . '/../../auto-generated/';
    public const CACHE_FILE = 'sokil-php-isocodes-cache.php';

    public static function createCacheRepresentation(Event $event): void
    {
        $event->getIO()->write('  Creating cache from sokil/php-isocodes-db-i18n ...');

        if (!is_dir(self::CACHE_DIR) ||
            !is_writable(self::CACHE_DIR)
        ) {
            $event->getIO()->write('  <error>ERROR: Cache directory ' . realpath(self::CACHE_DIR) . ' is not writable.</error>');
            return;
        }

        if (self::parseIsocodes()) {
            $event->getIO()->write('  <info>sokil/php-isocodes-db-i18n parsed and cached to ' . realpath(self::CACHE_DIR) . '</info>');
        } else {
            $event->getIO()->write('  <error>ERROR: sokil/php-isocodes-db-i18n could not be parsed and cached to ' . realpath(self::CACHE_DIR) . '</error>');
        }
    }

    public static function parseIsocodes(
        string $overrideInputJson = '',
        string $overrideOutputFile = '',
    ): bool
    {
        // TODO: Do the actual work

        $cacheContent = <<<EOF
<?php
declare(strict_types=1);

return [
    'de' => 'German',
    'en' => 'English',
    'ru' => 'Russian',
    'fr' => 'French',
];
EOF;

        if ($overrideOutputFile === '') {
            $outputFile = self::CACHE_DIR . self::CACHE_FILE;
        } else {
            $outputFile = $overrideOutputFile;
        }
        $bytes = file_put_contents(
            $outputFile,
            $cacheContent
        );

        if ($bytes === false) {
            return false;
        }

        return true;
    }
}
