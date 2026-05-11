<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class FormatDateViewHelper extends AbstractViewHelper
{
    /**
     * Register arguments.
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('date', 'string', 'Datum', true, '');
        $this->registerArgument('format', 'string', 'Output format', false, '');
        $this->registerArgument('as', 'string', 'name of the label result variable', false, null);
    }

    /**
     * @return string
     */
    public function render()
    {
        $date = trim((string)$this->arguments['date']);
        $valueName = trim((string)$this->arguments['as']);

        if ($date === '') {
            return $this->returnValue('', $valueName);
        }

        $parsedDate = $this->parseDateByPrecision($date);
        if ($parsedDate === null) {
            return $this->returnValue('', $valueName);
        }

        $format = trim((string)$this->arguments['format']);
        $resultValue = $format !== ''
            ? $this->formatByPattern($parsedDate, $format)
            : $this->formatByPrecision($parsedDate);

        return $this->returnValue($resultValue, $valueName);
    }

    private function returnValue(string $value, string $valueName): string
    {
        if ($valueName === '') {
            return $value;
        }

        if ($this->templateVariableContainer->exists($valueName)) {
            $this->templateVariableContainer->remove($valueName);
        }

        $this->templateVariableContainer->add($valueName, $value);

        return '';
    }

    private function parseDateByPrecision(string $date): ?array
    {
        $matched = preg_match(
            '/^(?<sign>[+-]?)(?<year>\d{4,})(?:-(?<month>\d{2})(?:-(?<day>\d{2}))?)?$/',
            $date,
            $matches
        );

        if (!$matched) {
            return null;
        }

        $sign = $matches['sign'] ?? '';
        $year = $matches['year'];
        $month = ($matches['month'] ?? '') !== '' ? (int)$matches['month'] : null;
        $day = ($matches['day'] ?? '') !== '' ? (int)$matches['day'] : null;

        if ($month !== null && ($month < 1 || $month > 12)) {
            return null;
        }

        if ($day !== null) {
            if ($month === null) {
                return null;
            }

            $yearValue = (int)$year;
            if ($sign === '-') {
                $yearValue *= -1;
            }

            if ($day < 1 || $day > $this->daysInMonth($yearValue, $month)) {
                return null;
            }
        }

        if ($day !== null) {
            $precision = 'day';
        } elseif ($month !== null) {
            $precision = 'month';
        } else {
            $precision = 'year';
        }

        return [
            'sign' => $sign,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'precision' => $precision,
        ];
    }

    private function formatByPrecision(array $parsedDate): string
    {
        $signedYear = $parsedDate['sign'] . $parsedDate['year'];

        if ($parsedDate['precision'] === 'year') {
            return $signedYear;
        }

        if ($parsedDate['precision'] === 'month') {
            return sprintf('%02d.%s', $parsedDate['month'], $signedYear);
        }

        return sprintf('%02d.%02d.%s', $parsedDate['day'], $parsedDate['month'], $signedYear);
    }

    private function formatByPattern(array $parsedDate, string $format): string
    {
        $signedYear = $parsedDate['sign'] . $parsedDate['year'];
        $month = $parsedDate['month'] === null ? '01' : sprintf('%02d', $parsedDate['month']);
        $day = $parsedDate['day'] === null ? '01' : sprintf('%02d', $parsedDate['day']);

        return strtr($format, [
            'Y' => $signedYear,
            'm' => $month,
            'd' => $day,
        ]);
    }

    private function daysInMonth(int $year, int $month): int
    {
        $daysPerMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        if ($month !== 2) {
            return $daysPerMonth[$month - 1];
        }

        $isLeapYear = (($year % 4) === 0 && ($year % 100) !== 0) || (($year % 400) === 0);
        if ($isLeapYear) {
            return 29;
        }

        return 28;
    }
}
