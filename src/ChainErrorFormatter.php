<?php

declare(strict_types=1);

namespace BackEndTea\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\Container;
use PHPStan\ShouldNotHappenException;

use function array_map;
use function max;
use function str_replace;

final class ChainErrorFormatter implements ErrorFormatter
{
    /** @var list<ErrorFormatter> */
    private array $formatters;

    /** @param list<string> $formatters */
    public function __construct(
        private Container $container,
        array $formatters,
    ) {
        $this->formatters = array_map(
            function (string $formatter): ErrorFormatter {
                $formatter = 'errorFormatter.' . str_replace('errorFormatter.', '', $formatter);

                $service =  $this->container->getService($formatter);
                if (! $service instanceof ErrorFormatter) {
                    throw new ShouldNotHappenException('Service ' . $formatter . ' is not an instance of ErrorFormatter');
                }

                return $service;
            },
            $formatters,
        );
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $highestExitCode = 0;
        foreach ($this->formatters as $errorFormatter) {
            $highestExitCode = max($highestExitCode, $errorFormatter->formatErrors($analysisResult, $output));
        }

        if ($highestExitCode) {
            return $highestExitCode;
        }

        return $analysisResult->hasErrors() ? 1 : 0;
    }
}
