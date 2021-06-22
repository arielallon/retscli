<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Output;

interface StrategyInterface
{
    public const FILEPATH = RETSCLI_ROOT_DIR . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR;

    public function setMlsKey(string $mls_key): StrategyInterface;
    public function outputResults(\ArrayAccess $results) : StrategyInterface;
}
