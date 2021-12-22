<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Command;

use ArielAllon\RetsCli\Configuration;
use ArielAllon\RetsCli\Output\MetadataJson;
use ArielAllon\RetsCli\Output\MetadataStrategyInterface;
use ArielAllon\RetsCli\Output\MetadataYaml;
use ArielAllon\RetsCli\PHRETS;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Metadata extends Command
{
    private const ARGUMENT_KEY = 'key';
    private const ARGUMENT_TYPE = 'type';
    private const ARGUMENT_RESOURCE_ALIAS = 'resource_alias';

    private const OPTION_RESOURCE = 'resource';
    private const OPTION_CLASS = 'class';
    private const OPTION_OUTPUT = 'output';

    private const KEY_RESOURCE = 'resource';
    private const KEY_CLASSES = 'classes';

    private const METADATA_TYPES = [
        'system',
        'resources',
        'classes',
        'table',
//        'search'
    ];

    protected static $defaultName = 'metadata';

    private InputInterface $input;
    private OutputInterface $output;
    private \PHRETS\Session $phrets_session;
    private string $resource_alias;
    private array $resources_and_classes;
    private bool $standard_names;
    private ProgressBar $progress_bar;

    protected function configure()
    {
        $this->setDescription('Request metadata from the RETS server')
            ->addArgument(
                self::ARGUMENT_KEY,
                InputArgument::REQUIRED,
                'Key of configuration to use'
            )
            ->addArgument(
                self::ARGUMENT_TYPE,
                InputArgument::REQUIRED,
                'Type of metadata to request. Valid options: ' . implode(', ', self::METADATA_TYPES)
            )
            ->addArgument(
                self::ARGUMENT_RESOURCE_ALIAS,
                InputArgument::REQUIRED,
                'Alias in config file for resource+class(es) to query'
            )
            ->addOption(
                self::OPTION_RESOURCE,
                'r',
                InputOption::VALUE_OPTIONAL,
                'Specific resource for this query. If not provided, will run against all in config file.',
                null
            )
            ->addOption(
                self::OPTION_CLASS,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Specific class for this query. If not provided, will run against all in config file.',
                null
            )
            ->addOption(
                self::OPTION_OUTPUT,
                'O',
                InputOption::VALUE_OPTIONAL,
                'Specifies the output file for the data. Current possibilities: json, yaml',
                null
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->validateOptionsCombinations($input, $output);

        $this->setInput($input);
        $this->setOutput($output);

        $mlsConfigurationArray = (new Configuration\FromYaml())->getConfigurationByKey(
            $input->getArgument(self::ARGUMENT_KEY)
        );

        $this->setPhretsSession((new PHRETS\SessionBuilder())->fromConfigurationArray($mlsConfigurationArray))
            ->setResourceAlias($input->getArgument(self::ARGUMENT_RESOURCE_ALIAS))
            ->initializeResourcesAndClasses($input, $mlsConfigurationArray)
            ->setStandardNames($mlsConfigurationArray['standard_names'] ?? false) // @todo remove?
        ;
    }

    /**
     * $input and $output will generally be used from the getters, and were set in initialize.
     * they are still in the signature as required by the Command parent class
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->phretsLogin();
        foreach ($this->getResourcesAndClasses()[self::KEY_CLASSES] as $class) {
            $resource = $this->getResourcesAndClasses()[self::KEY_RESOURCE];
            $output->writeln('Resource: ' . $resource);
            $output->writeln('Class: ' . $class);

            $dataOutput = $this->getOutputStrategy();
            if ($dataOutput !== null) {
                $dataOutput->setMlsKey($input->getArgument(self::ARGUMENT_KEY))
                    ->setResourceName($resource)
                    ->setClassName($class)
                    ->setTypeName($input->getArgument(self::ARGUMENT_TYPE));
            }

            switch ($input->getArgument(self::ARGUMENT_TYPE)) {
                case 'system':
                    // @todo this is resource+class-independent, no need to loop over those, nor include them in the filename. should also warn that those configs/params are ignored.
                    $results = $this->buildSystemMetadata();
                    break;
                case 'resources':
                    // @todo this is class-independent, no need to loop over those, nor include them in the filename. should also warn that those configs/params are ignored.
                    $results = $this->buildResourcesMetadata($resource);
                    break;
                case 'classes':
                    // @todo this is class-independent, no need to loop over those, nor include them in the filename. should also warn that those configs/params are ignored.
                    $results = $this->buildClassesMetadata($resource);
                    break;
                case 'table':
                    $results = $this->buildTableMetadata($resource, $class);
                    $this->sortTableResults($results);
                    break;
                default:
                    throw new \RuntimeException(
                        sprintf(
                            "Invalid metadata type provided: '%s'. Must be one of : %s",
                            $input->getArgument(self::ARGUMENT_TYPE),
                            implode(', ', self::METADATA_TYPES)
                        )
                    );
            }

            if ($dataOutput !== null) {
                $dataOutput->outputResults($results);
            } else {
                $output->write(var_export($results, true));
            }

            if ($dataOutput !== null) {
                $dataOutput->complete();
            }
            $output->writeln('');
        }
        $this->getPhretsSession()->Disconnect();
        return Command::SUCCESS;
    }

    private function buildSystemMetadata(): \PHRETS\Models\Metadata\System
    {
        return $this->getPhretsSession()->GetSystemMetadata();
    }

    private function buildResourcesMetadata(string $resource)
    {
        return $this->getPhretsSession()->GetResourcesMetadata($resource);
    }

    private function buildClassesMetadata(string $resource)
    {
        return $this->getPhretsSession()->GetClassesMetadata($resource);
    }

    private function buildTableMetadata(string $resource, string $class): array
    {
        $output = [];

        $table = $this->getPhretsSession()->GetTableMetadata($resource, $class);
        foreach ($table->all() as $fieldName => $field) {
            $lookupFieldName = null;
            $output[$fieldName] = [];
            if (isset($field['SystemName'])) {
                $output[$fieldName]['SystemName'] = $field['SystemName'];
            }
            if (isset($field['LongName'])) {
                $output[$fieldName]['LongName'] = $field['LongName'];
            }
            if (isset($field['DataType'])) {
                $output[$fieldName]['DataType'] = $field['DataType'];
            }
            if (isset($field['LookupName'])) {
                $lookupFieldName = $field['LookupName'] ?? null;
            }
            if (!empty($lookupFieldName)) {
                $valuesCollection = $this->getPhretsSession()->GetLookupValues($resource, $lookupFieldName);
                foreach ($valuesCollection->all() as $value) {
                    $output[$fieldName]['Values'][] = $value['LongValue'];
                }
            }
        }

        return $output;
    }

    private function getOutputStrategy(): ?MetadataStrategyInterface
    {
        switch ($this->getInput()->getOption(self::OPTION_OUTPUT)) {
            case 'json':
                return new MetadataJson();
            case 'yaml':
                return new MetadataYaml();
            default:
                throw new \RuntimeException(
                    sprintf(
                        "Invalid output format provided: '%s'. Format must be one of: json, yaml",
                        $this->getInput()->getOption(self::OPTION_OUTPUT)
                    )
                );
        }
    }

    private function validateOptionsCombinations(InputInterface $input, OutputInterface $output): self
    {
        if ($input->getOption(self::OPTION_RESOURCE) === null xor $input->getOption(self::OPTION_CLASS) === null) {
            throw new \RuntimeException('If a class is specified, a resource must also be specified, and vice versa');
        }

        if (!in_array($input->getArgument(self::ARGUMENT_TYPE), self::METADATA_TYPES)) {
            throw new \RuntimeException(
                sprintf(
                    'Type of metadata must be one of [%s]',
                    implode(', ', self::METADATA_TYPES)
                )
            );
        }

        return $this;
    }

    private function initializeResourcesAndClasses(InputInterface $input, array $mlsConfigurationArray): self
    {
        $specificResource = $input->getOption(self::OPTION_RESOURCE);
        $specificClass = $input->getOption(self::OPTION_CLASS);
        if ($specificResource !== null && $specificClass !== null) {
            $this->setResourcesAndClasses(
                [
                    self::KEY_RESOURCE => $specificResource,
                    self::KEY_CLASSES => [$specificClass],
                ]
            );
        } else {
            $this->setResourcesAndClasses($mlsConfigurationArray['resources'][$this->getResourceAlias()]);
        }

        return $this;
    }

    private function phretsLogin(): self
    {
        // Some RETS servers inexplicably fail on the first login but succeed if you try again.
        try {
            $this->getPhretsSession()->Login();
        } catch (\Exception $e) {
            $this->getPhretsSession()->Login();
        }

        return $this;
    }

    private function sortTableResults(array &$array, int $flags = SORT_REGULAR): void
    {
        if ($this->array_is_list($array)) {
            sort($array, $flags);
        } else {
            ksort($array, $flags);
        }

        foreach ($array as $k=>$v) {
            if (is_array($array[$k])) {
                $this->sortTableResults($array[$k], $flags);
            }
        }
    }

    /**
     * polyfill for PHP 8.1
     * @link https://wiki.php.net/rfc/is_list#proposal
     */
    function array_is_list(array $array): bool {
        $expectedKey = 0;
        foreach ($array as $i => $_) {
            if ($i !== $expectedKey) { return false; }
            $expectedKey++;
        }
        return true;
    }

    private function getInput(): InputInterface
    {
        return $this->input; // Will throw if uninitialized
    }

    private function setInput(InputInterface $input): self
    {
        try {
            $this->input; // Attempt to read
        } catch (\Error $e) {
            $this->input = $input; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Metadata input is already set.');
    }

    private function getOutput(): OutputInterface
    {
        return $this->output; // Will throw if uninitialized
    }

    private function setOutput(OutputInterface $output): self
    {
        try {
            $this->output; // Attempt to read
        } catch (\Error $e) {
            $this->output = $output; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Metadata output is already set.');
    }

    private function getPhretsSession(): \PHRETS\Session
    {
        return $this->phrets_session; // Will throw if uninitialized
    }

    private function setPhretsSession(\PHRETS\Session $phrets_session): self
    {
        try {
            $this->phrets_session; // Attempt to read
        } catch (\Error $e) {
            $this->phrets_session = $phrets_session; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Metadata phrets_session is already set.');
    }

    private function getResourceAlias(): string
    {
        return $this->resource_alias; // Will throw if uninitialized
    }

    private function setResourceAlias(string $resource_alias): self
    {
        try {
            $this->resource_alias; // Attempt to read
        } catch (\Error $e) {
            $this->resource_alias = $resource_alias; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Metadata resource_alias is already set.');
    }

    private function getResourcesAndClasses(): array
    {
        return $this->resources_and_classes; // Will throw if uninitialized
    }

    private function setResourcesAndClasses(array $resources_and_classes): self
    {
        try {
            $this->resources_and_classes; // Attempt to read
        } catch (\Error $e) {
            $this->resources_and_classes = $resources_and_classes; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Metadata resources_and_classes is already set.');
    }

    private function getStandardNames(): bool
    {
        return $this->standard_names; // Will throw if uninitialized
    }

    private function setStandardNames(bool $standard_names): self
    {
        try {
            $this->standard_names; // Attempt to read
        } catch (\Error $e) {
            $this->standard_names = $standard_names; // Variable hasn't been initialized
            return $this;
        }

        throw new \LogicException('Metadata standard_names is already set.');
    }
}
