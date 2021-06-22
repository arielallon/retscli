<?php

declare(strict_types=1);

namespace ArielAllon\RetsCli\Command;

use ArielAllon\RetsCli\Configuration;
use ArielAllon\RetsCli\Output\MediaBinary;
use ArielAllon\RetsCli\PHRETS;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ObjectQuery extends Command
{
    private const ARGUMENT_KEY = 'key';
    private const ARGUMENT_ID = 'id';
    private const ARGUMENT_RESOURCE_ALIAS = 'resource_alias';

    private const OPTION_RESOURCE = 'resource';
    private const OPTION_CLASS = 'class';
    private const OPTION_FIELD = 'field';
    private const OPTION_OBJECT_ID = 'object_id';
    private const OPTION_BY_LOCATION = 'by_location';
    private const OPTION_SAVE_BINARIES = 'save_binaries';
    private const OPTION_PHP_MEMORY_LIMIT = 'php_memory_limit';

    protected static $defaultName = 'objectquery';

    private InputInterface $input;
    private OutputInterface $output;
    private array $mls_configuration;

    /** @var \PHRETS\Session */
    private $phrets_session;

    /** @var string */
    private $resource_alias;

    /** @var string */
    private $resource;

    protected function configure()
    {
        $this->setDescription('Send a GetObject query to the RETS server')
            ->addArgument(
                self::ARGUMENT_KEY,
                InputArgument::REQUIRED,
                'Key of configuration to use'
            )
            ->addArgument(
                self::ARGUMENT_ID,
                InputArgument::REQUIRED,
                'Id to use in query to RETS server'
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
                self::OPTION_FIELD,
                'f',
                InputOption::VALUE_OPTIONAL,
                'Name of field of media object. Overrides value in config file.',
                null
            )
            ->addOption(
                self::OPTION_OBJECT_ID,
                'i',
                InputOption::VALUE_OPTIONAL,
                'Id(s) of the object to request (usually an index). Defaults to *.',
                '*'
            )
            ->addOption(
                self::OPTION_BY_LOCATION,
                null,
                InputOption::VALUE_NONE,
                'Will request the locations (URLs) of the media, otherwise requests binaries. Overrides value in config file.',
                null
            )
            ->addOption(
                self::OPTION_SAVE_BINARIES,
                null,
                InputOption::VALUE_NONE,
                'Save the binaries from the response.',
                null
            )
            ->addOption(
                self::OPTION_PHP_MEMORY_LIMIT,
                'm',
                InputOption::VALUE_OPTIONAL,
                'Override the default php memory_limit value',
                null
            )
            ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::OPTION_PHP_MEMORY_LIMIT) !== null) {
            ini_set('memory_limit', $input->getOption(self::OPTION_PHP_MEMORY_LIMIT));
        }

        $this->setInput($input);
        $this->setOutput($output);
        $this->setMlsConfiguration((new Configuration\FromYaml())->getConfigurationByKey(
            $input->getArgument(self::ARGUMENT_KEY)
        ));

        $this->setPhretsSession((new PHRETS\SessionBuilder())->fromConfigurationArray($this->getMlsConfiguration()))
            ->setResourceAlias($input->getArgument(self::ARGUMENT_RESOURCE_ALIAS))
            ->initializeResource();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->phretsLogin();
        $resource = $this->getResource();
        $output->writeln('Resource: ' . $resource);

        $offset = null;

        $results = $this->getPhretsSession()->GetObject(
            $resource,
            $this->getOptionValue(self::OPTION_FIELD, "resources|{$this->getResourceAlias()}|object|field"),
            $input->getArgument(self::ARGUMENT_ID),
            $input->getOption(self::OPTION_OBJECT_ID),
            $this->getOptionValue(self::OPTION_BY_LOCATION, "resources|{$this->getResourceAlias()}|object|by_location"),
        );

        $this->outputResults($results);

        $output->writeln('');
        $this->getPhretsSession()->Disconnect();
        return Command::SUCCESS;
    }

    private function outputResults(\Illuminate\Support\Collection $results)
    {
        $this->outputResultsToStdOut($results);
        if ($this->getInput()->getOption(self::OPTION_SAVE_BINARIES)) {
            $mediaBinaryOutputer = new MediaBinary();
            $mediaBinaryOutputer->setMlsKey($this->getInput()->getArgument(self::ARGUMENT_KEY))
                ->setContentId($this->getInput()->getArgument(self::ARGUMENT_ID));
            $mediaBinaryOutputer->outputResults($results);
        }
    }

    private function outputResultsToStdOut(\Illuminate\Support\Collection $results): void
    {
        $includeLocation = $this->getOptionValue(
            self::OPTION_BY_LOCATION,
            "resources|{$this->getResourceAlias()}|object|by_location"
        );

        $resultsForOutput = [];

        /** @var \PHRETS\Models\BaseObject $result */
        foreach ($results as $result) {
            $resultArray = [
                'ContentId' => $result->getContentId(),
                'ContentDescription' => $result->getContentDescription(),
                'ContentType' => $result->getContentType(),
                'ObjectId' => $result->getObjectId(),
            ];
            if ((int)$includeLocation === 1) {
                $resultArray['Location'] = $result->getLocation();
            }
            $resultsForOutput[] = $resultArray;
        }
        $this->getOutput()->write(json_encode($resultsForOutput, JSON_PRETTY_PRINT));
    }

    private function initializeResource(): self
    {
        $this->setResource(
            $this->getOptionValue(self::OPTION_RESOURCE, "resources|{$this->getResourceAlias()}|resource")
        );
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

    private function getOptionValue(string $cliOptionKey, string $configurationPathPipeNotation)
    {
        $cliValue = $this->getInput()->hasParameterOption('--' . $cliOptionKey) ? $this->getInput()->getOption($cliOptionKey) : null;
        if ($cliValue !== null) {
            return $cliValue;
        } else {
            $configurationPathSteps = explode('|', $configurationPathPipeNotation);
            $configurationStep = $this->getMlsConfiguration();
            foreach ($configurationPathSteps as $nextStep) {
                $configurationStep = $configurationStep[$nextStep];
            }
            return $configurationStep;
        }
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
    }


    private function getMlsConfiguration(): array
    {
        return $this->mls_configuration; // Will throw if uninitialized
    }

    private function setMlsConfiguration(array $mls_configuration): self
    {
        try {
            $this->mls_configuration; // Attempt to read
        } catch (\Error $e) {
            $this->mls_configuration = $mls_configuration; // Variable hasn't been initialized
            return $this;
        }
    }


    private function getPhretsSession(): \PHRETS\Session
    {
        if ($this->phrets_session === null) {
            throw new \LogicException('ObjectQuery phretsSession has not been set.');
        }

        return $this->phrets_session;
    }

    private function setPhretsSession(\PHRETS\Session $phretsSession): self
    {
        if ($this->phrets_session !== null) {
            throw new \LogicException('ObjectQuery phretsSession already set.');
        }

        $this->phrets_session = $phretsSession;

        return $this;
    }

    private function getResourceAlias(): string
    {
        if ($this->resource_alias === null) {
            throw new \LogicException('ObjectQuery resource_alias has not been set.');
        }

        return $this->resource_alias;
    }

    private function setResourceAlias(string $resource_alias): self
    {
        if ($this->resource_alias !== null) {
            throw new \LogicException('ObjectQuery resource_alias already set.');
        }

        $this->resource_alias = $resource_alias;

        return $this;
    }

    private function getResource(): string
    {
        if ($this->resource === null) {
            throw new \LogicException('ObjectQuery resource has not been set.');
        }

        return $this->resource;
    }

    private function setResource(string $resource): self
    {
        if ($this->resource !== null) {
            throw new \LogicException('ObjectQuery resource already set.');
        }

        $this->resource = $resource;

        return $this;
    }
}
