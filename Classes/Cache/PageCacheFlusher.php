<?php

declare(strict_types=1);

namespace Porthd\Timer\Cache;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2024 Dr. Dieter Porth <info@mobger.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Cache\CacheManager;

/**
 * PURPOSE: Flush the page cache for given page id(s) through the stable core cache-tag mechanism,
 *          replacing the @internal `TYPO3\CMS\Extbase\Service\CacheService::clearPageCache()`.
 *
 * ADVANTAGES:
 *   - Removes the extension's dependency on an explicitly @internal Extbase class (upgrade-critical),
 *     confining the still-untagged-but-stable `CacheManager` usage to this single adapter.
 *   - Keeps the call signature identical to the previous `clearPageCache()` so the data processors need
 *     only a type swap, no call-site changes.
 *
 * PRECONDITIONS (data requirements):
 *   - Page caches are tagged `pageId_<uid>` (TYPO3 core convention) — same tags CacheService used.
 *
 * EDGE CASES:
 *   - null / empty selection: no tags → CacheManager flushes nothing for that call (caller guards this).
 */
final class PageCacheFlusher
{
    public function __construct(
        private readonly CacheManager $cacheManager,
    ) {}

    /**
     * Flushes the page cache for a single page id or a list of page ids.
     *
     * @param int|int[] $pageIdsToClear
     */
    public function clearPageCache(int|array $pageIdsToClear): void
    {
        $pageIds = is_array($pageIdsToClear) ? $pageIdsToClear : [$pageIdsToClear];
        $tags = array_map(static fn(int $pageId): string => 'pageId_' . $pageId, $pageIds);
        $this->cacheManager->flushCachesInGroupByTags('pages', $tags);
    }
}
