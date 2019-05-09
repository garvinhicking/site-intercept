<?php
declare(strict_types=1);

namespace App\Tests\Functional\Service;

use App\Bundle\ClockMockBundle;
use App\Client\GeneralClient;
use App\Entity\DocumentationJar;
use App\Exception\Composer\DependencyException;
use App\Extractor\PushEvent;
use App\Service\DocumentationBuildInformationService;
use App\Service\MailService;
use App\Tests\Functional\DatabasePrimer;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DocumentationBuildInformationServiceTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $packageName = 'foobar/baz';

    /**
     * @var string
     */
    private $branch = 'master';

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        DatabasePrimer::prime(self::$kernel);

        ClockMockBundle::withClockMock();

        $this->entityManager = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @test
     */
    public function buildFileIsGenerated(): void
    {
        $currentTime = microtime(true);
        $currentTimeInt = ceil($currentTime * 10000);
        ClockMockBundle::register(DocumentationBuildInformationService::class);
        ClockMockBundle::withClockMock($currentTime);

        $pushEvent = $this->getPushEvent();
        $subject = new DocumentationBuildInformationService(
            '/tmp/',
            '/tmp/',
            'docs-build-information',
            $this->entityManager,
            new Filesystem(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->getClientProphecy(
                $pushEvent->getUrlToComposerFile(),
                200,
                json_encode([
                    'name' => $this->packageName,
                    'type' => 'typo3-cms-framework',
                    'authors' => [
                        ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
                    ],
                    'require' => ['typo3/cms-core' => '*'],
                ])
            )->reveal(),
            $this->prophesize(MailService::class)->reveal()
        );

        $buildInformation = $subject->generateBuildInformation($pushEvent);

        $this->assertSame('docs-build-information/' . $currentTimeInt, $buildInformation->getFilePath());
        $this->assertFileExists('/tmp/docs-build-information/' . $currentTimeInt);

        $expectedFileContent = [
            '#!/bin/bash',
            'vendor=foobar',
            'name=baz',
            'branch=master',
            'target_branch_directory=master',
            'type_long=core-extension',
            'type_short=c',
            'repository_url=http://myserver.com/foobar/baz.git',
            ''
        ];
        $this->assertSame(implode(PHP_EOL, $expectedFileContent), file_get_contents('/tmp/docs-build-information/' . $currentTimeInt));
    }

    /**
     * @test
     */
    public function renderAttemptOnForkThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1553090750);

        $originalRepository = (new DocumentationJar())
            ->setRepositoryUrl('http://there-can-be-only-one.com/' . $this->packageName . '.git')
            ->setBranch('1.0.0')
            ->setPackageName($this->packageName);

        $this->entityManager->persist($originalRepository);
        $this->entityManager->flush();

        $pushEvent = $this->getPushEvent();
        $subject = new DocumentationBuildInformationService(
            '/tmp/',
            '/tmp/',
            'docs-build-information',
            $this->entityManager,
            new Filesystem(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->getClientProphecy(
                $pushEvent->getUrlToComposerFile(),
                200,
                json_encode([
                    'name' => $this->packageName,
                    'type' => 'typo3-cms-framework',
                    'authors' => [
                        ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
                    ],
                    'require' => ['typo3/cms-core' => '*'],
                ])
            )->reveal(),
            $this->prophesize(MailService::class)->reveal()
        );

        $subject->generateBuildInformation($pushEvent);
    }

    /**
     * @test
     */
    public function notExistingComposerJsonThrowsException(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionCode(1553081065);

        $pushEvent = $this->getPushEvent();
        $subject = new DocumentationBuildInformationService(
            '/tmp/',
            '/tmp/',
            'docs-build-information',
            $this->entityManager,
            new Filesystem(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->getClientProphecy(
                $pushEvent->getUrlToComposerFile(),
                404,
                ''
            )->reveal(),
            $this->prophesize(MailService::class)->reveal()
        );

        $subject->generateBuildInformation($pushEvent);
    }

    /**
     * @test
     */
    public function fallbackToTypePackageIsLogged(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->info(Argument::any())->shouldBeCalled();

        $pushEvent = $this->getPushEvent();
        $subject = new DocumentationBuildInformationService(
            '/tmp/',
            '/tmp/',
            'docs-build-information',
            $this->entityManager,
            new Filesystem(),
            $loggerProphecy->reveal(),
            $this->getClientProphecy(
                $pushEvent->getUrlToComposerFile(),
                200,
                json_encode([
                    'name' => $this->packageName,
                    'type' => 'something-that-triggers-fallback-to-package',
                    'authors' => [
                        ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
                    ],
                    'require' => ['typo3/cms-core' => '*'],
                ])
            )->reveal(),
            $this->prophesize(MailService::class)->reveal()
        );

        $subject->generateBuildInformation($pushEvent);
    }

    /**
     * @test
     */
    public function missingCoreDependencyThrowsExceptionAndSendsMailIfGiven(): void
    {
        $this->expectException(DependencyException::class);
        $this->expectExceptionCode(1557310527);

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error(Argument::cetera())->shouldBeCalled();

        $message = new \Swift_Message();

        $mailServiceProphecy = $this->prophesize(MailService::class);
        $mailServiceProphecy->createMessageWithTemplate(Argument::cetera())->shouldBeCalled()->willReturn($message);
        $mailServiceProphecy->send($message)->shouldBeCalled()->willReturn(1);

        $pushEvent = $this->getPushEvent();
        $subject = new DocumentationBuildInformationService(
            '/tmp/',
            '/tmp/',
            'docs-build-information',
            $this->entityManager,
            new Filesystem(),
            $loggerProphecy->reveal(),
            $this->getClientProphecy(
                $pushEvent->getUrlToComposerFile(),
                200,
                json_encode([
                    'name' => $this->packageName,
                    'type' => 'typo3-cms-extension',
                    'authors' => [
                        ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
                    ],
                ])
            )->reveal(),
            $mailServiceProphecy->reveal()
        );

        $subject->generateBuildInformation($pushEvent);
    }

    /**
     * @test
     */
    public function missingCoreDependencyDoesNotSendMailIfNotGiven(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error(Argument::cetera())->shouldBeCalled();

        $mailServiceProphecy = $this->prophesize(MailService::class);
        $mailServiceProphecy->createMessageWithTemplate(Argument::cetera())->shouldNotBeCalled();
        $mailServiceProphecy->send(Argument::any())->shouldNotBeCalled();

        $pushEvent = $this->getPushEvent();
        $subject = new DocumentationBuildInformationService(
            '/tmp/',
            '/tmp/',
            'docs-build-information',
            $this->entityManager,
            new Filesystem(),
            $loggerProphecy->reveal(),
            $this->getClientProphecy(
                $pushEvent->getUrlToComposerFile(),
                200,
                json_encode([
                    'name' => $this->packageName,
                    'type' => 'typo3-cms-extension',
                    'authors' => [
                        ['name' => 'John Doe'],
                    ],
                ])
            )->reveal(),
            $mailServiceProphecy->reveal()
        );

        $subject->generateBuildInformation($pushEvent);
    }

    /**
     * @test
     */
    public function onlyOneRecordPerRepositoryAndBranchIsCreatedOnConsecutiveCalls(): void
    {
        $iterations = 3;
        for ($i = 0; $i < $iterations; ++$i) {
            $pushEvent = $this->getPushEvent();
            $subject = new DocumentationBuildInformationService(
                '/tmp/',
                '/tmp/',
                'docs-build-information',
                $this->entityManager,
                new Filesystem(),
                $this->prophesize(LoggerInterface::class)->reveal(),
                $this->getClientProphecy(
                    $pushEvent->getUrlToComposerFile(),
                    200,
                    json_encode([
                        'name' => $this->packageName,
                        'type' => 'typo3-cms-framework',
                        'authors' => [
                            ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
                        ],
                        'require' => ['typo3/cms-core' => '*'],
                    ])
                )->reveal(),
                $this->prophesize(MailService::class)->reveal()
            );

            $subject->generateBuildInformation($pushEvent);
        }

        $this->assertCount(1, $this->entityManager->getRepository(DocumentationJar::class)->findAll());
    }

    /**
     * @return PushEvent
     */
    private function getPushEvent(): PushEvent
    {
        return new PushEvent(
            'http://myserver.com/' . $this->packageName . '.git',
            $this->branch,
            'https://raw.githubusercontent.com/' . $this->packageName . '/' . $this->branch . '/composer.json'
        );
    }

    /**
     * @param string $url
     * @param int $statusCode
     * @param string $responseBody
     * @return ObjectProphecy
     */
    private function getClientProphecy(string $url, int $statusCode, string $responseBody): ObjectProphecy
    {
        /** @var GeneralClient|ObjectProphecy $generalClientProphecy */
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy->request('GET', $url)->shouldBeCalled()->willReturn(
            new Response($statusCode, [], $responseBody)
        );

        return $generalClientProphecy;
    }
}