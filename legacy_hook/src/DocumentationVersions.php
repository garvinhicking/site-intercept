<?php
declare(strict_types = 1);

namespace App;

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Finder\Finder;

/**
 * Return a list of other versions of given documentation
 */
class DocumentationVersions
{
    protected $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Creates a HTML response which contains a list if <dd> tags with links to all versions
     * @return Response
     */
    public function getVersions(): Response
    {
        $pathSegments = $this->resolvePathSegments();
        if (count($pathSegments) < 5) {
            return $this->getEmptyResponse();
        }

        // first three segments are main root of that repo - eg. '[p, lolli42, enetcache]'
        $entryPoint = implode('/', array_slice($pathSegments, 0, 3));
        // 'current' called version, eg. 'master', or '9.5'
        $currentVersion = array_slice($pathSegments, 3, 1)[0];
        // 'current' called language eg. 'en-us'
        $currentLanguage = array_slice($pathSegments, 4, 1)[0];
        // further path to currently viewed sub file, eg. '[subPage, Index.html]'
        $pathAfterEntryPoint = array_slice($pathSegments, 5);
        // ensure singlehtml is not part of the path (see handling for singlepath below) - @see resolvePathInformation
        if (($pathAfterEntryPoint[0] ?? '') === 'singlehtml') {
            unset($pathAfterEntryPoint[0]);
        }
        $absolutePathToDocsEntryPoint = $GLOBALS['_SERVER']['DOCUMENT_ROOT'] . '/' . $entryPoint;

        if (empty($currentVersion) || empty($currentLanguage)
            || (
                // verify entry path exists and current version and full path actually exist
                // this additionally sanitizes the input url
                !is_dir($absolutePathToDocsEntryPoint)
                || !is_dir($absolutePathToDocsEntryPoint . '/' . $currentVersion . '/' . $currentLanguage)
                || !file_exists($absolutePathToDocsEntryPoint . '/' . $currentVersion . '/' . $currentLanguage . '/' . implode('/', $pathAfterEntryPoint))
            )
        ) {
            return $this->getEmptyResponse();
        }

        // find versions and language variants of this project
        $validatedVersions = $this->resolveVersionsAndLanguages($absolutePathToDocsEntryPoint);
        // final version entries
        $validatedVersions = $this->resolvePathInformation($validatedVersions, $pathAfterEntryPoint, $absolutePathToDocsEntryPoint);

        return $this->getHTMLResponse($validatedVersions);
    }

    protected function resolvePathSegments(): array
    {
        $url = $this->request->getQueryParams()['url'] ?? '';
        // /p/vendor/package/version/some/sub/page/Index.html/
        $urlPath = '/' . trim(parse_url($url)['path'] ?? '', '/') . '/';

        // Simple path traversal protection: remove '/../' and '/./'
        $urlPath = str_replace(['/../', '/./'], '', $urlPath);

        // Remove leading and trailing slashes again
        return explode('/', trim($urlPath, '/'));
    }

    protected function resolveVersionsAndLanguages(string $filePathToDocsEntryPoint): array
    {
        /** @var Finder $finder */
        $finder = (new Finder())
            ->directories()
            ->in($filePathToDocsEntryPoint)
            ->depth('1');
        $validatedVersions = [];
        if ($finder->hasResults()) {
            foreach ($finder as $result) {
                [$version, $language] = explode('/', $result->getRelativePathname());
                $validatedVersions[] = [
                    'version' => $version,
                    'language' => $language
                ];
            }
        }
        return $validatedVersions;
    }

    protected function resolvePathInformation(array $validatedVersions, array $pathAfterEntryPoint, string $absolutePathToDocsEntryPoint): array
    {
        $entries = [];
        // One entry per version that is deployed
        foreach ($validatedVersions as $validatedVersion) {
            $checkSubPaths = $pathAfterEntryPoint;
            $subPathCount = count($checkSubPaths);
            $found = false;
            for ($i = 0; $i < $subPathCount; $i++) {
                // Traverse sub path segments up until one has been found in filesystem, to find the
                // "nearest" matching version of currently viewed file
                $pathToCheck = $absolutePathToDocsEntryPoint . '/' . $validatedVersion['version'] . '/' . $validatedVersion['language'] . '/' . implode('/', $checkSubPaths);
                if (is_file($pathToCheck) || is_dir($pathToCheck)) {
                    $validatedVersion['path'] = $pathToCheck;
                    $found = true;
                    break;
                }
                array_pop($checkSubPaths);
            }
            if (!$found) {
                $validatedVersion['path'] = $absolutePathToDocsEntryPoint . '/' . $validatedVersion['version'] . '/' . $validatedVersion['language'] . '/';
            }
            $singleHtmlPath = $absolutePathToDocsEntryPoint . '/' . $validatedVersion['version'] . '/' . $validatedVersion['language'] . '/singlehtml/';
            if (is_file($singleHtmlPath) || is_dir($singleHtmlPath)) {
                $validatedVersion['single_path'] = $singleHtmlPath;
            }
            $entries[] = $validatedVersion;
        }
        return $entries;
    }

    protected function getHTMLResponse(array $entries): Response
    {
        $firstEntries = [];
        $secondEntries = [];
        $versions = array_column($entries, 'version');
        array_multisort($versions, SORT_ASC, $entries);
        foreach ($entries as $entry) {
            $url = str_replace($GLOBALS['_SERVER']['DOCUMENT_ROOT'], '', $entry['path'] ?? '');
            $singleUrl = str_replace($GLOBALS['_SERVER']['DOCUMENT_ROOT'], '', $entry['single_path'] ?? '');
            $title = $entry['version'] . ' ' . $entry['language'];
            $firstEntries[] = $url !== '' ? '<dd><a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5) . '">' . htmlspecialchars($title, ENT_QUOTES | ENT_HTML5) . '</a></dd>' : '';
            $secondEntries[] = $singleUrl !== '' ? '<dd><a href="' . htmlspecialchars($singleUrl, ENT_QUOTES | ENT_HTML5) . '">' . htmlspecialchars('In one file: ' . $title, ENT_QUOTES | ENT_HTML5) . '</a></dd>' : '';
        }

        $firstEntries = array_reverse($firstEntries);
        $secondEntries = array_reverse($secondEntries);
        return new Response(200, [], implode(chr(10), array_merge($firstEntries, $secondEntries)));
    }

    protected function getEmptyResponse(): Response
    {
        return new Response(200, [], '');
    }
}
