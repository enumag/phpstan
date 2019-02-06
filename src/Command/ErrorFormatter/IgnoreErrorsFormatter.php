<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Nette\Utils\Strings;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\File\RelativePathHelper;
use Symfony\Component\Console\Style\OutputStyle;
use Nette\Neon\Neon;

class IgnoreErrorFormatter implements ErrorFormatter
{
	public function formatErrors(
		AnalysisResult $analysisResult,
		OutputStyle $style
	): int
	{
		if (!$analysisResult->hasErrors()) {
			return 0;
		}

		$style->writeln('# This is an auto-generated file, do not edit.');
		$style->writeln('#');
		$style->writeln('# You can regenerate this file using this command:');
		$style->writeln('# cat /dev/null > phpstan-ignore.neon && vendor/phpstan/phpstan/bin/phpstan analyse --configuration=phpstan.neon <your configuration> --error-format=ignore > phpstan-ignore.neon');
		$style->writeln('#');
		$style->writeln('# Make sure this file is listed in the include section of your phpstan.neon.');
		$style->writeln('');
		$style->writeln('parameters:');
		$style->writeln('    ignoreErrors:');

		$errors = [];

		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			$file = $fileSpecificError->getFile();
			$file = Strings::before($file, ' (in context of class ') ?: $file;
			$errors[$file][] = $fileSpecificError->getMessage();
		}

		foreach ($errors as $filename => $fileErrors) {
			$fileErrors = array_unique($fileErrors);

			foreach ($fileErrors as $message) {
				// Can't use $style->writeln() because it messes up neon formatting.
				echo $this->formatError($filename, $message) . PHP_EOL;
			}
		}

		return 1;
	}

	public function formatError(string $filename, string $message): string
	{
		return sprintf(
			"        -\n            message: %s\n            path: %s",
			Neon::encode('~' . preg_quote(substr($message, 0, -1), '~') . '~'),
			Neon::encode('%currentWorkingDirectory%' . Strings::after($filename, getcwd()))
		);
	}

}
