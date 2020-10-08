<?php
declare(strict_types=1);

namespace ArielAllon\RetsCli\Configuration;

use Symfony\Component\Yaml\Yaml;

class FromYaml implements FromYamlInterface
{
    /** @var array */
    private $configurationArray;

    public function getConfigurationByKey(string $key) : array
    {
        if (!isset($this->getConfigurationArray()[$key])) {
            // @todo throw
        }
        return $this->getConfigurationArray()[$key];
    }

    private function loadConfigurationFromFile() : FromYamlInterface
    {
        $configurationArray = Yaml::parseFile(RETSCLI_ROOT_DIR . self::CONFIGURATION_FILE_NAME);
        $this->setConfigurationArray($configurationArray);
        return $this;
    }

    private function getConfigurationArray(): array
    {
        if ($this->configurationArray === null) {
            $this->loadConfigurationFromFile();
        }

        return $this->configurationArray;
    }

    private function setConfigurationArray(array $configurationArray): FromYamlInterface
    {
        if ($this->configurationArray !== null) {
            throw new \LogicException('FromYaml configurationArray already set.');
        }

        $this->configurationArray = $configurationArray;

        return $this;
    }
}
