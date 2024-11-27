<?php

declare(strict_types=1);

namespace BackEndTea\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use PHPStan\ShouldNotHappenException;

use function file_put_contents;
use function hash;
use function implode;
use function json_encode;

use const JSON_PRETTY_PRINT;

/** @see https://docs.gitlab.com/ee/user/project/merge_requests/code_quality.html#implementing-a-custom-tool */
final class GitlabFileErrorFormatter implements ErrorFormatter
{
    public function __construct(
        private RelativePathHelper $relativePathHelper,
        private string $fileLocation,
    ) {
        if (! $this->fileLocation) {
            throw new ShouldNotHappenException('File location must be set with `errorFormatters.gitlabFile`');
        }
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        $errorsArray = [];

        foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
            $error = [
                'description' => $fileSpecificError->getMessage(),
                'fingerprint' => hash(
                    'sha256',
                    implode(
                        [
                            $fileSpecificError->getFile(),
                            $fileSpecificError->getLine(),
                            $fileSpecificError->getMessage(),
                        ],
                    ),
                ),
                'severity' => $fileSpecificError->canBeIgnored() ? 'major' : 'blocker',
                'location' => [
                    'path' => $this->relativePathHelper->getRelativePath($fileSpecificError->getFile()),
                    'lines' => [
                        'begin' => $fileSpecificError->getLine() ?? 0,
                    ],
                ],
            ];

            $errorsArray[] = $error;
        }

        foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
            $errorsArray[] = [
                'description' => $notFileSpecificError,
                'fingerprint' => hash('sha256', $notFileSpecificError),
                'severity' => 'major',
                'location' => [
                    'path' => '',
                    'lines' => ['begin' => 0],
                ],
            ];
        }

        $json = json_encode($errorsArray, JSON_PRETTY_PRINT);
        file_put_contents($this->relativePathHelper->getRelativePath($this->fileLocation), $json);

        return $analysisResult->hasErrors() ? 1 : 0;
    }
}
