<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

interface MetadataJsonInterface extends MetadataStrategyInterface
{
    public const FILENAME_FORMAT = '%s_%s_%s_%s.json';
}
