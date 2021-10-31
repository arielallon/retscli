<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

interface MetadataYamlInterface extends MetadataStrategyInterface
{
    public const FILENAME_FORMAT = '%s_%s_%s_%s.yaml';
}
