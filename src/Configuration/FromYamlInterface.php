<?php
declare(strict_types=1);

namespace ArielAllon\RetsCli\Configuration;

interface FromYamlInterface
{
    public const CONFIGURATION_FILE_NAME = 'mls-configs.yml';

    public function getConfigurationByKey(string $key) : array;
}
