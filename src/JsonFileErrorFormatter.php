<?php

declare(strict_types=1);

namespace BackEndTea\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPStan\ShouldNotHappenException;

use function array_key_exists;
use function count;
use function file_put_contents;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

final class JsonFileErrorFormatter implements ErrorFormatter
{
    public function __construct(
        private RelativePathHelper $relativePathHelper,
        private bool $pretty,
        private string $fileLocation,
    ) {
        if (! $this->fileLocation) {
            throw new ShouldNotHappenException('File location must be set with `errorFormatters.jsonFile`');
        }
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $errorsArray = [
            'totals' => [
                'errors' => count($analysisResult->getNotFileSpecificErrors()),
                'file_errors' => count($analysisResult->getFileSpecificErrors()),
            ],
            'files' => [],
            'errors' => [],
        ];

        foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
            $file = $fileSpecificError->getFile();
            if (! array_key_exists($file, $errorsArray['files'])) {
                $errorsArray['files'][$file] = [
                    'errors' => 0,
                    'messages' => [],
                ];
            }

            $errorsArray['files'][$file]['errors']++;

            $message = [
                'message' => $fileSpecificError->getMessage(),
                'line' => $fileSpecificError->getLine(),
                'ignorable' => $fileSpecificError->canBeIgnored(),
            ];

            if ($fileSpecificError->getTip() !== null) {
                $message['tip'] = $fileSpecificError->getTip();
            }

            if ($fileSpecificError->getIdentifier() !== null) {
                $message['identifier'] = $fileSpecificError->getIdentifier();
            }

            $errorsArray['files'][$file]['messages'][] = $message;
        }

        foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
            $errorsArray['errors'][] = $notFileSpecificError;
        }

        $flags = JSON_THROW_ON_ERROR;
        if ($this->pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($errorsArray, $flags);

        file_put_contents($this->relativePathHelper->getRelativePath($this->fileLocation), $json);

        return $analysisResult->hasErrors() ? 1 : 0;
    }
}
