<?php

declare(strict_types=1);

namespace Cobweb\ExternalImport\Context;

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

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This is a concrete implementation of the call context for the CLI context.
 *
 * @package Cobweb\ExternalImport\Step
 */
class CommandLineCallContext extends AbstractCallContext
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * Sets the SymfonyStyle component for formatted output.
     *
     * @param SymfonyStyle $io
     */
    public function setInputOutput(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    /**
     * Outputs the debug data in the terminal.
     *
     * @param string $message Message to display
     * @param int $severity Degree of severity
     * @param mixed $data Additional data to display
     * @return void
     */
    public function outputDebug(string $message, int $severity, $data): void
    {
        if ($this->importer->isVerbose()) {
            switch ($severity) {
                case -1:
                    $status = 'OK';
                    break;
                case 1:
                    $status = 'NOTICE';
                    break;
                case 2:
                    $status = 'WARNING';
                    break;
                case 3:
                    $status = 'ERROR';
                    break;
                default:
                    $status = 'INFO';
            }
            $this->io->writeln('-------------------------------------------------------------------');
            $this->io->writeln('DEBUG [' . $status . ']: ' . $message);
            $this->io->writeln(var_export($data, true));
            $this->io->writeln('-------------------------------------------------------------------');
        }
    }
}