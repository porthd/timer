<?php

declare(strict_types=1);

namespace Porthd\Timer\Tests\Unit\CodeQuality;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * PURPOSE: Static guard that every `use`-import in the extension's PHP classes still resolves to a
 *          loadable class/interface/trait/enum, that used TYPO3 core classes are part of the public
 *          API surface (@api / not @internal), and — one level deeper — that the individual TYPO3
 *          methods invoked via statically resolvable static calls are themselves API (per-method
 *          @api / @internal), not just the class.
 *
 * ADVANTAGES:
 *   - Detects "dead" namespaces (e.g. classes renamed/removed during a TYPO3 major upgrade) BEFORE
 *     runtime, without executing the affected code path.
 *   - Ignores trait-uses (`use SomeTrait;` inside a class body) and closure-uses (`function () use (...)`)
 *     because the AST distinguishes them from namespace imports — no short-name false positives.
 *   - Method-level API check narrows the class-level noise: a class may be undeclared while the exact
 *     method you call is @api (stable) or @internal (upgrade-critical) — that distinction is what
 *     actually matters for a major upgrade.
 *
 * DISADVANTAGES / TRADE-OFFS:
 *   - Relies on the composer autoloader: a class is only "resolvable" if its package is installed and
 *     PSR-4 mapped. That is exactly the property we want to assert, but it means the extension's own
 *     `require` must declare every used TYPO3 system extension (core, backend, extbase, fluid, ...).
 *   - The per-method check only covers STATIC calls whose class is statically resolvable at the call
 *     site (`GeneralUtility::makeInstance(...)`, `ExtensionManagementUtility::...`, fully-qualified
 *     `\TYPO3\CMS\...::...`). Instance calls (`$obj->method()`) need type inference and are NOT checked
 *     — resolving the receiver's runtime type reliably is out of scope for a lightweight AST guard.
 *
 * PRECONDITIONS (data requirements):
 *   - nikic/php-parser (v5) available via the autoloader.
 *   - The autoloader that runs the test knows all imported packages.
 *
 * EDGE CASES:
 *   - `use function` / `use const` imports are skipped (only class-like imports are checked).
 *   - Grouped imports `use A\{B, C as D};` are expanded to their fully qualified names.
 *   - Autoloading a broken class may throw \Throwable during resolution → treated as unresolved.
 *   - `self::` / `static::` / `parent::` static calls are skipped (they target the analysed class, not
 *     a TYPO3 API surface). Calls to methods that reflection cannot find (magic `__callStatic`,
 *     method defined on a child type) are classified 'unknown' and NOT reported.
 */
#[Group('codeQuality')]
final class DeadNamespaceImportTest extends TestCase
{
    /**
     * Directories scanned for PHP class files. Covers ALL own extensions under `MyExtension/*`, so a
     * single test run guards the whole repository against dead namespaces (add new extensions here).
     * Non-existent paths are skipped silently by the provider, so this stays valid if an extension is
     * removed.
     *
     * @var array<int, string>
     */
    private const SCAN_DIRS = [
        __DIR__ . '/../../../Classes',                      // timer (this extension)
        __DIR__ . '/../../../../buergerstimmen/Classes',
        __DIR__ . '/../../../../fragments/Classes',
        __DIR__ . '/../../../../ichschauweg/Classes',
        __DIR__ . '/../../../../reaction/Classes',
        __DIR__ . '/../../../../skeleton/Classes',
        __DIR__ . '/../../../../storyteller/Classes',
        __DIR__ . '/../../../../webhelp/Classes',
    ];

    private const TYPO3_PREFIX = 'TYPO3\\CMS\\';

    /**
     * Env flag: when set to a truthy value, the soft checks additionally report the (large, low-signal)
     * "undeclared" bucket. Default output is the small, high-signal @internal list only.
     */
    private const VERBOSE_ENV = 'TIMER_A7_VERBOSE';

    /**
     * Allowlist of TYPO3 classes that are de-facto stable extension-developer API but carry no @api tag
     * (TYPO3 tags @api sparingly). They are filtered out of the "undeclared" bucket only — an @internal
     * classification is NEVER suppressed by this list, so a real upgrade risk can never be hidden here.
     *
     * @var array<int, string>
     */
    private const KNOWN_STABLE = [
        'TYPO3\\CMS\\Core\\Utility\\GeneralUtility',
        'TYPO3\\CMS\\Core\\Utility\\MathUtility',
        'TYPO3\\CMS\\Core\\Utility\\ExtensionManagementUtility',
        'TYPO3\\CMS\\Core\\Context\\Context',
        'TYPO3\\CMS\\Core\\Information\\Typo3Version',
        'TYPO3\\CMS\\Core\\Database\\ConnectionPool',
        'TYPO3\\CMS\\Extbase\\Utility\\LocalizationUtility',
        'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity',
        'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage',
        'TYPO3\\CMS\\Extbase\\Persistence\\Repository',
        'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
        'TYPO3\\CMS\\Frontend\\ContentObject\\DataProcessorInterface',
    ];

    /**
     * Yields one dataset per PHP file: [label => [relativeLabel, absolutePath]].
     *
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function phpFileProvider(): iterable
    {
        foreach (self::SCAN_DIRS as $dir) {
            $root = realpath($dir);
            if ($root === false || !is_dir($root)) {
                continue;
            }
            // Prefix the dataset label with the extension key (parent dir of `Classes`) so labels stay
            // unique across the multiple scanned roots — otherwise same-named files in two extensions
            // (e.g. DataProcessing/Foo.php) would collide as duplicate data-provider keys.
            $extension = basename(dirname($root));
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );
            /** @var \SplFileInfo $fileInfo */
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'php') {
                    continue;
                }
                $absolute = $fileInfo->getPathname();
                $relative = ltrim(str_replace($root, '', $absolute), DIRECTORY_SEPARATOR);
                $label = $extension . '/' . $relative;
                yield $label => [$label, $absolute];
            }
        }
    }

    /**
     * Hard assertion: every class-like `use`-import must resolve. Unresolvable imports fail the test.
     */
    #[Test]
    #[DataProvider('phpFileProvider')]
    public function classImportsResolve(string $label, string $absolutePath): void
    {
        $dead = [];
        foreach (self::collectClassImports($absolutePath) as $fqcn) {
            if (!self::isResolvable($fqcn)) {
                $dead[] = $fqcn;
            }
        }

        self::assertSame(
            [],
            $dead,
            sprintf(
                "Dead namespace import(s) in %s (class/interface/trait no longer resolvable):\n  - %s",
                $label,
                implode("\n  - ", $dead)
            )
        );
    }

    /**
     * Soft check: used TYPO3 core classes should be public API. Emits an E_USER_WARNING (does NOT fail
     * the build) for classes marked @internal (always) or lacking any @api declaration (only in verbose
     * mode and unless allowlisted as de-facto stable). @internal is the high-signal, upgrade-critical
     * bucket; "undeclared" is low-signal noise and off by default.
     */
    #[Test]
    #[DataProvider('phpFileProvider')]
    public function typo3ImportsArePublicApi(string $label, string $absolutePath): void
    {
        $internal = [];
        $undeclared = [];

        foreach (self::collectClassImports($absolutePath) as $fqcn) {
            if (strncmp($fqcn, self::TYPO3_PREFIX, strlen(self::TYPO3_PREFIX)) !== 0) {
                continue;
            }
            if (!self::isResolvable($fqcn)) {
                // Unresolvable imports are the concern of classImportsResolve().
                continue;
            }
            switch (self::apiStatus($fqcn)) {
                case 'internal':
                    $internal[] = $fqcn;
                    break;
                case 'undeclared':
                    if (!in_array($fqcn, self::KNOWN_STABLE, true)) {
                        $undeclared[] = $fqcn;
                    }
                    break;
                    // 'api' => stable, nothing to report
            }
        }

        // Keep the test from being reported as "risky" (it legitimately may have no hard assertion).
        $this->addToAssertionCount(1);

        $message = self::buildApiMessage(
            sprintf('TYPO3 API check for %s:', $label),
            '@internal — NOT stable API, upgrade-critical',
            $internal,
            'no @api declaration — use with care',
            $undeclared
        );
        if ($message !== null) {
            trigger_error($message, E_USER_WARNING);
        }
    }

    /**
     * Soft check (method-level): for every statically resolvable static call into a TYPO3 core class,
     * verify the CALLED METHOD is public API. Same severity model as the class check — @internal methods
     * always reported, "undeclared" only in verbose mode and unless the class is allowlisted as stable.
     */
    #[Test]
    #[DataProvider('phpFileProvider')]
    public function typo3StaticMethodCallsArePublicApi(string $label, string $absolutePath): void
    {
        $ast = self::parse($absolutePath);
        $aliasMap = self::collectImportAliasMap($ast);

        $internal = [];
        $undeclared = [];

        foreach (self::collectTypo3StaticCalls($ast, $aliasMap) as $call) {
            $signature = $call['class'] . '::' . $call['method'] . '()';
            switch (self::apiStatusMethod($call['class'], $call['method'])) {
                case 'internal':
                    $internal[$signature] = $signature;
                    break;
                case 'undeclared':
                    if (!in_array($call['class'], self::KNOWN_STABLE, true)) {
                        $undeclared[$signature] = $signature;
                    }
                    break;
                    // 'api' => stable; 'unknown' => not classifiable → nothing to report
            }
        }

        // Keep the test from being reported as "risky" (it legitimately may have no hard assertion).
        $this->addToAssertionCount(1);

        $message = self::buildApiMessage(
            sprintf('TYPO3 method-level API check for %s:', $label),
            '@internal method — NOT stable API, upgrade-critical',
            array_values($internal),
            'method without @api declaration — use with care',
            array_values($undeclared)
        );
        if ($message !== null) {
            trigger_error($message, E_USER_WARNING);
        }
    }

    /**
     * Assembles the warning message for a soft API check, applying the severity model:
     *   - the @internal bucket is always included (high signal, upgrade-critical);
     *   - the "undeclared" bucket is included only when verbose mode ({@see VERBOSE_ENV}) is on.
     * Returns null when there is nothing to report (→ caller emits no warning).
     *
     * @param array<int, string> $internal
     * @param array<int, string> $undeclared
     */
    private static function buildApiMessage(
        string $header,
        string $internalLabel,
        array $internal,
        string $undeclaredLabel,
        array $undeclared
    ): ?string {
        $showUndeclared = self::isVerbose() && $undeclared !== [];
        if ($internal === [] && !$showUndeclared) {
            return null;
        }

        $message = $header;
        if ($internal !== []) {
            $message .= "\n  [" . $internalLabel . "]:\n    - " . implode("\n    - ", $internal);
        }
        if ($showUndeclared) {
            $message .= "\n  [" . $undeclaredLabel . "]:\n    - " . implode("\n    - ", $undeclared);
        }

        return $message;
    }

    private static function isVerbose(): bool
    {
        $value = getenv(self::VERBOSE_ENV);

        return $value !== false && $value !== '' && $value !== '0';
    }

    /**
     * Parses a PHP file into an AST (shared by the collectors below).
     *
     * @return array<int, \PhpParser\Node\Stmt>
     */
    private static function parse(string $absolutePath): array
    {
        $code = file_get_contents($absolutePath);
        self::assertNotFalse($code, 'Unable to read ' . $absolutePath);

        $parser = (new ParserFactory())->createForHostVersion();

        return $parser->parse($code) ?? [];
    }

    /**
     * Extracts fully qualified class-like imports from a PHP file (skips `use function` / `use const`
     * and, by AST design, trait- and closure-uses).
     *
     * @return array<int, string> unique list of fully qualified names (no leading backslash)
     */
    private static function collectClassImports(string $absolutePath): array
    {
        $ast = self::parse($absolutePath);

        $finder = new NodeFinder();
        $names = [];

        /** @var Use_ $use */
        foreach ($finder->findInstanceOf($ast, Use_::class) as $use) {
            if ($use->type !== Use_::TYPE_NORMAL) {
                continue; // function/const import
            }
            foreach ($use->uses as $item) {
                $names[] = $item->name->toString();
            }
        }

        /** @var GroupUse $group */
        foreach ($finder->findInstanceOf($ast, GroupUse::class) as $group) {
            $prefix = $group->prefix->toString();
            foreach ($group->uses as $item) {
                $effectiveType = $item->type !== Use_::TYPE_UNKNOWN ? $item->type : $group->type;
                if ($effectiveType !== Use_::TYPE_NORMAL) {
                    continue;
                }
                $names[] = $prefix . '\\' . $item->name->toString();
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * True if the fully qualified name is a loadable class, interface, trait or enum.
     */
    private static function isResolvable(string $fqcn): bool
    {
        try {
            return class_exists($fqcn)
                || interface_exists($fqcn)
                || trait_exists($fqcn)
                || (function_exists('enum_exists') && enum_exists($fqcn));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Classifies a resolvable class as 'api' (public), 'internal' (explicitly non-API) or 'undeclared'
     * (no @api tag). A class counts as 'api' when the class docblock OR any of its own public methods
     * carry an @api tag — matching TYPO3's method-level API tagging.
     */
    private static function apiStatus(string $fqcn): string
    {
        try {
            $reflection = new \ReflectionClass($fqcn);
        } catch (\Throwable) {
            return 'undeclared';
        }

        $classDoc = $reflection->getDocComment() ?: '';
        if (self::hasTag($classDoc, 'api')) {
            return 'api';
        }

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            if (self::hasTag($method->getDocComment() ?: '', 'api')) {
                return 'api';
            }
        }

        return self::hasTag($classDoc, 'internal') ? 'internal' : 'undeclared';
    }

    /**
     * Builds a map of the file's class-like imports: local alias/short-name => fully qualified name.
     * Used to resolve the class of a static call written with its short name (`GeneralUtility::...`).
     *
     * @param array<int, \PhpParser\Node\Stmt> $ast
     * @return array<string, string>
     */
    private static function collectImportAliasMap(array $ast): array
    {
        $finder = new NodeFinder();
        $map = [];

        /** @var Use_ $use */
        foreach ($finder->findInstanceOf($ast, Use_::class) as $use) {
            if ($use->type !== Use_::TYPE_NORMAL) {
                continue;
            }
            foreach ($use->uses as $item) {
                $fqcn = $item->name->toString();
                $alias = $item->alias?->toString() ?? self::shortName($fqcn);
                $map[$alias] = $fqcn;
            }
        }

        /** @var GroupUse $group */
        foreach ($finder->findInstanceOf($ast, GroupUse::class) as $group) {
            $prefix = $group->prefix->toString();
            foreach ($group->uses as $item) {
                $effectiveType = $item->type !== Use_::TYPE_UNKNOWN ? $item->type : $group->type;
                if ($effectiveType !== Use_::TYPE_NORMAL) {
                    continue;
                }
                $fqcn = $prefix . '\\' . $item->name->toString();
                $alias = $item->alias?->toString() ?? self::shortName($fqcn);
                $map[$alias] = $fqcn;
            }
        }

        return $map;
    }

    /**
     * Collects static calls (`Class::method(...)`) whose class statically resolves to a TYPO3 core
     * class. Returns one entry per unique class/method pair.
     *
     * @param array<int, \PhpParser\Node\Stmt> $ast
     * @param array<string, string> $aliasMap
     * @return array<int, array{class: string, method: string}>
     */
    private static function collectTypo3StaticCalls(array $ast, array $aliasMap): array
    {
        $finder = new NodeFinder();
        $calls = [];

        /** @var StaticCall $call */
        foreach ($finder->findInstanceOf($ast, StaticCall::class) as $call) {
            if (!($call->class instanceof Name) || !($call->name instanceof Identifier)) {
                continue; // dynamic class or dynamic method name → not statically resolvable
            }
            $fqcn = self::resolveStaticCallClass($call->class, $aliasMap);
            if ($fqcn === null || strncmp($fqcn, self::TYPO3_PREFIX, strlen(self::TYPO3_PREFIX)) !== 0) {
                continue;
            }
            $method = $call->name->toString();
            $calls[$fqcn . '::' . $method] = ['class' => $fqcn, 'method' => $method];
        }

        return array_values($calls);
    }

    /**
     * Resolves the class name of a static call to a fully qualified name, or null when it cannot be
     * resolved statically (relative same-namespace name, or self/static/parent).
     *
     * @param array<string, string> $aliasMap
     */
    private static function resolveStaticCallClass(Name $name, array $aliasMap): ?string
    {
        if ($name->isFullyQualified()) {
            return ltrim($name->toString(), '\\');
        }

        $parts = $name->getParts();
        $first = $parts[0];
        if (in_array($first, ['self', 'static', 'parent'], true)) {
            return null;
        }
        if (!isset($aliasMap[$first])) {
            return null; // e.g. a class in the same namespace, not import-resolvable here
        }

        $rest = array_slice($parts, 1);

        return $aliasMap[$first] . ($rest === [] ? '' : '\\' . implode('\\', $rest));
    }

    /**
     * Classifies a single method as 'api', 'internal', 'undeclared' or 'unknown' (method not found via
     * reflection — e.g. magic __callStatic). The method's own docblock wins; otherwise a class-level
     * @internal marks the method internal too.
     */
    private static function apiStatusMethod(string $fqcn, string $method): string
    {
        try {
            $reflection = new \ReflectionClass($fqcn);
        } catch (\Throwable) {
            return 'unknown';
        }
        if (!$reflection->hasMethod($method)) {
            return 'unknown';
        }

        $methodDoc = $reflection->getMethod($method)->getDocComment() ?: '';
        if (self::hasTag($methodDoc, 'api')) {
            return 'api';
        }
        if (self::hasTag($methodDoc, 'internal')) {
            return 'internal';
        }

        return self::hasTag($reflection->getDocComment() ?: '', 'internal') ? 'internal' : 'undeclared';
    }

    private static function shortName(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }

    private static function hasTag(string $docComment, string $tag): bool
    {
        return $docComment !== '' && preg_match('/@' . preg_quote($tag, '/') . '\b/', $docComment) === 1;
    }
}
