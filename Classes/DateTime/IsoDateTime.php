<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\DateTime;

/**
 * DateTime which prefers ISO (YYYY-MM-DD) format and can be used as value in Fluid forms
 */
final class IsoDateTime extends \DateTime implements \Stringable
{
    public const FORMAT = 'Y-m-d';

    public function __toString(): string
    {
        return $this->format(self::FORMAT);
    }

    public static function createFromFormat($format, $time, $timezone = null): \DateTime|false
    {
        $formats = [
            'Y-m-d',
            'd.m.Y',
        ];

        foreach ($formats as $format) {
            $date = parent::createFromFormat($format, $time, $timezone);
            if ($date !== false) {
                return new self('@' . $date->getTimestamp());
            }
        }

        return false;
    }

    public function toDateTime(): \DateTime
    {
        return (new \DateTime())->setTimestamp($this->getTimestamp());
    }
}
