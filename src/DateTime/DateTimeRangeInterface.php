<?php
declare(strict_types=1);

namespace ArielAllon\RetsCli\DateTime;

interface DateTimeRangeInterface
{
    public function getStartDateTime() : \DateTimeInterface;
    public function setStartDateTime(\DateTimeInterface $startDateTime) : DateTimeRangeInterface;

    public function getEndDateTime() : \DateTimeInterface;
    public function setEndDateTime(\DateTimeInterface $endDateTime) : DateTimeRangeInterface;
}
