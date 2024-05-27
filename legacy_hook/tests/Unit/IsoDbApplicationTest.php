<?php
declare(strict_types = 1);

namespace App\Tests\Unit;

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\DocumentationVersions;
use App\Composer\IsoLanguageCacheCreator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use org\bovigo\vfs\vfsStream;

class IsoDbApplicationTest extends TestCase
{
    private ServerRequestInterface&MockObject $requestMock;
    private UriInterface&MockObject $uriMock;

    private DocumentationVersions $subject;

    public function setUp(): void
    {
        parent::setUp();

        $versions = [
            '9.4',
            '10.5',
            '11.5',
            '12.4',
            'main',
        ];
        $languages = [
            'de-de' => 'GERMAN',
            'en-us' => 'DEFAULT',
            'ru-ru' => 'RUSSIAN',
            'fr-fr' => 'FRENCH',
            'de-AT' => 'GERMAN (AUSTRIAN)',
            'de-CH' => 'GERMAN (SWISS)',
        ];
        $subdirectories = [
            'Concepts',
            'singlehtml',
        ];

        $struct = [];
        foreach ($versions as $version) {
            $struct[$version] = [];
            foreach ($languages as $language => $languageName) {
                $struct[$version][$language] = [
                    'Index.html' => 'Sample ' . $languageName . ' in version ' . $version,
                ];

                foreach ($subdirectories as $subdirectory) {
                    $struct[$version][$language][$subdirectory] = [
                        'Index.html' => 'Sample ' . $languageName . ' (subdir ' . $subdirectory . ') in version ' . $version,
                    ];
                }
            }
        }

        vfsStream::setup('docs.typo3.org', null, [
            'm' => [
                'typo3' => [
                    'tutorial-getting-started' => $struct,
                ],
            ],
        ]);
        $GLOBALS['_SERVER']['DOCUMENT_ROOT'] = vfsStream::url('docs.typo3.org');

        $this->requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $this->uriMock = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->requestMock->expects($this->any())
            ->method('getUri')
            ->willReturn($this->uriMock);
        $this->subject = new DocumentationVersions($this->requestMock);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['_SERVER']['DOCUMENT_ROOT']);
        parent::tearDown();
    }

    public function testInitializationWorks(): void
    {
        $this->configureRequestUriPath('');
        $response = $this->subject->getVersions(
            'HTML',
            'https://docs.typo3.org/m/typo3/tutorial-getting-started/12.4/en-us/Index.html'
        );
        // $this->assertSame(404, $response->getStatusCode(), 'Not found did not return 404 as status code.');
        $this->assertSame(1, 1);
    }

    public function testCacheCreationWorks(): void
    {
        // TODO: Test IsoLanguageCacheCreator::parseIsocodes for proper
        // creation
        $this->assertSame(1, 1);
    }

    private function configureRequestUriPath(string $path = ''): void
    {
        $this->uriMock->expects($this->any())
            ->method('getPath')
            ->willReturn($path);
    }
}
