<?php

namespace Cobweb\ExternalImport\Tests\Domain\Repository;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Cobweb\ExternalImport\Domain\Model\Configuration;
use Cobweb\ExternalImport\Domain\Repository\UidRepository;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test class for the UidRepository
 * @package Cobweb\ExternalImport\Tests\Domain\Repository
 */
class UidRepositoryTest extends FunctionalTestCase
{

    /**
     * @var UidRepository
     */
    protected $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->subject = GeneralUtility::makeInstance(UidRepository::class);
    }

    public function configurationDataProvider(): array
    {
        return [
            'No special configuration, no pid restriction' => [
                [
                    'referenceUid' => 'tstamp'
                ],
                [
                    1520788063 => 2,
                    1520788087 => 3
                ],
                [
                    1520788063 => 1,
                    1520788087 => 2
                ]
            ],
            'Pid restriction true' => [
                [
                    'referenceUid' => 'tstamp',
                    'enforcePid' => true
                ],
                [
                    1520788063 => 2
                ],
                [
                    1520788063 => 1
                ]
            ],
            'Pid restriction true-ish' => [
                [
                    'referenceUid' => 'tstamp',
                    'enforcePid' => 1
                ],
                [
                    1520788063 => 2
                ],
                [
                    1520788063 => 1
                ]
            ],
            'Pid restriction other than true' => [
                [
                    'referenceUid' => 'tstamp',
                    'enforcePid' => false
                ],
                [
                    1520788063 => 2,
                    1520788087 => 3
                ],
                [
                    1520788063 => 1,
                    1520788087 => 2
                ]
            ],
            'Where clause' => [
                [
                    'referenceUid' => 'tstamp',
                    'whereClause' => 'header like \'%deleted%\''
                ],
                [
                    1520788087 => 3
                ],
                [
                    1520788087 => 2
                ]
            ]
        ];
    }

    /**
     * @param array $configuration
     * @param array $listOfUids
     * @param array $listOfPids
     * @test
     * @dataProvider configurationDataProvider
     * @throws \Nimut\TestingFramework\Exception\Exception
     * @throws \Cobweb\ExternalImport\Exception\MissingConfigurationException
     */
    public function getExistingUidsTriggersFetchingOfUidsAndPids(array $configuration, array $listOfUids, array $listOfPids): void
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/UidRepository.xml');
        $configurationObject = GeneralUtility::makeInstance(Configuration::class);
        $configurationObject->setTable('tt_content');
        $configurationObject->setGeneralConfiguration($configuration);
        if ($configuration['enforcePid']) {
            $configurationObject->setStoragePid(1);
        }
        $this->subject->setConfiguration($configurationObject);
        self::assertSame(
            $listOfUids,
            $this->subject->getExistingUids()
        );
        self::assertSame(
            $listOfPids,
            $this->subject->getCurrentPids()
        );
    }

    /**
     * @test
     * @expectedException \Cobweb\ExternalImport\Exception\MissingConfigurationException
     */
    public function getExistingUidsWithoutConfigurationThrowsException(): void
    {
        $this->subject->getExistingUids();
    }
}