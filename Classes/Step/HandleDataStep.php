<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Step;

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

use Cobweb\ExternalImport\DataHandlerInterface;
use Cobweb\ExternalImport\Exception\CriticalFailureException;
use Cobweb\ExternalImport\Handler\ArrayHandler;
use Cobweb\ExternalImport\Handler\XmlHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This step takes the raw data from the "read" step and makes it into a structured
 * array, ready for further processing.
 *
 * @package Cobweb\ExternalImport\Step
 */
class HandleDataStep extends AbstractStep
{

    /**
     * Maps the external data to TCA fields.
     *
     * @return void
     */
    public function run(): void
    {
        $generalConfiguration = $this->importer->getExternalConfiguration()->getGeneralConfiguration();
        $originalData = $this->getData()->getRawData();
        // Check for custom data handlers
        if (!empty($generalConfiguration['dataHandler'])) {
            try {
                /** @var $dataHandler DataHandlerInterface */
                $dataHandler = GeneralUtility::makeInstance($generalConfiguration['dataHandler']);
                if ($dataHandler instanceof DataHandlerInterface) {
                    $records = $dataHandler->handleData(
                        $originalData,
                        $this->importer
                    );
                } else {
                    $this->abortFlag = true;
                    LocalizationUtility::translate(
                        'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:invalidCustomHandler',
                        'external_import',
                        array($generalConfiguration['dataHandler'])
                    );
                    return;
                }
            } catch (\Exception $e) {
                $this->abortFlag = true;
                LocalizationUtility::translate(
                    'LLL:EXT:external_import/Resources/Private/Language/ExternalImport.xlf:wrongCustomHandler',
                    'external_import',
                    array($generalConfiguration['dataHandler'])
                );
                return;
            }
            // Use default handlers
        } else {
            // Prepare the data, depending on result type
            switch ($generalConfiguration['data']) {
                case 'xml':
                    $xmlHandler = GeneralUtility::makeInstance(XmlHandler::class);
                    $records = $xmlHandler->handleData(
                        $originalData,
                        $this->importer
                    );
                    break;
                case 'array':
                    $arrayHandler = GeneralUtility::makeInstance(ArrayHandler::class);
                    $records = $arrayHandler->handleData(
                        $originalData,
                        $this->importer
                    );
                    break;

                // This should really not happen
                default:
                    $records = $originalData;
                    break;
            }
        }

        // Apply any existing pre-processing hook to the raw data
        try {
            $records = $this->preprocessRawData($records);
        } catch (CriticalFailureException $e) {
            // If a critical failure occurred during hook execution, set the abort flag and return to controller
            $this->setAbortFlag(true);
            return;
        }

        // Set the records in the Data object (and also as preview, if activated)
        $this->getData()->setRecords($records);
        $this->setPreviewData($records);
    }

    /**
     * Applies any existing pre-processing to the data before it moves on to the next step.
     *
     * Note that this method does not do anything by itself. It just calls on a pre-processing hook.
     *
     * @param array $records Records containing the mapped data
     * @return array
     * @throws CriticalFailureException
     */
    protected function preprocessRawData(array $records): array
    {
        // Using a hook is deprecated
        // TODO: remove in the next major version
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['preprocessRawRecordset'] ?? null;
        if (is_array($hooks)) {
            trigger_error('Hook "preprocessRawRecordset" is deprecated. Use a custom step instead.', E_USER_DEPRECATED);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['preprocessRawRecordset'] as $className) {
                try {
                    $preProcessor = GeneralUtility::makeInstance($className);
                    $records = $preProcessor->preprocessRawRecordset($records, $this->importer);
                    // Compact the array again, in case some values were unset in the pre-processor
                    $records = array_values($records);
                } catch (CriticalFailureException $e) {
                    // This exception must not be caught here, but thrown further up
                    throw $e;
                } catch (\Exception $e) {
                    $this->importer->debug(
                        sprintf(
                            'Could not instantiate class %s for hook %s',
                            $className,
                            'preprocessRawRecordset'
                        ),
                        1
                    );
                }
            }
        }
        return $records;
    }
}