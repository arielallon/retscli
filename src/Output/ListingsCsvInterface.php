<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

interface ListingsCsvInterface extends StrategyInterface
{
    public const FILENAME_FORMAT = '%s_%s_%s_%s.csv';

    public function setResourceName(string $resource_name): ListingsCsvInterface;
    public function setClassName(string $class_name): ListingsCsvInterface;
    public function complete() : ListingsCsvInterface;
}
