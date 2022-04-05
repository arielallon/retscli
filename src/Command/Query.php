<?php
declare(strict_types=1);

namespace ArielAllon\RetsCli\Command;

use ArielAllon\RetsCli\Configuration;
use ArielAllon\RetsCli\Output\ListingsCsv;
use ArielAllon\RetsCli\Output\StrategyInterface;
use ArielAllon\RetsCli\PHRETS;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Query extends Command
{
    private const ARGUMENT_KEY = 'key';
    private const ARGUMENT_QUERY = 'query';
    private const ARGUMENT_RESOURCE_ALIAS = 'resource_alias';

    private const OPTION_RESOURCE = 'resource';
    private const OPTION_CLASS = 'class';
    private const OPTION_OFFSET = 'offset';
    private const OPTION_LIMIT = 'limit';
    private const OPTION_COUNT = 'count';
    private const OPTION_SELECT = 'select';
    private const OPTION_OUTPUT = 'output';
    private const OPTION_NO_PROGRESS_BAR = 'no-progress-bar';

    private const KEY_RESOURCE = 'resource';
    private const KEY_CLASSES = 'classes';

    protected static $defaultName = 'query';

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    /** @var \PHRETS\Session */
    private $phrets_session;

    /** @var string */
    private $resource_alias;

    /** @var array */
    private $resources_and_classes;

    /** @var bool */
    private $standard_names;

    /** @var ProgressBar */
    private $progress_bar;

    protected function configure()
    {
        $this->setDescription('Send a Search query to the RETS server')
            ->addArgument(
                self::ARGUMENT_KEY,
                InputArgument::REQUIRED,
                'Key of configuration to use'
            )
            ->addArgument(
                self::ARGUMENT_QUERY,
                InputArgument::REQUIRED,
                'Query to send to RETS server'
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
                self::OPTION_OFFSET,
                'o',
                InputOption::VALUE_OPTIONAL,
                'Starting offset for query.',
                0
            )
            ->addOption(
                self::OPTION_LIMIT,
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit for query.',
                100
            )
            ->addOption(
                self::OPTION_COUNT,
                'C',
                InputOption::VALUE_NONE,
                'Is this a count query.'
            )
            ->addOption(
                self::OPTION_SELECT,
                's',
                InputOption::VALUE_OPTIONAL,
                'Specific fields to select. Comma-separated SystemNames.',
                null
            )
            ->addOption(
                self::OPTION_OUTPUT,
                'O',
                InputOption::VALUE_OPTIONAL,
                'Specifies the output file format for the data. Current possibilities: csv',
                null
            )
            ->addOption(
                self::OPTION_NO_PROGRESS_BAR,
                'Q',
                InputOption::VALUE_NONE,
                'Turns off progress bar in output'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->validateOptionsCombinations($input, $output);

        $this->setInput($input);
        $this->setOutput($output);

        $mlsConfigurationArray = (new Configuration\FromYaml())->getConfigurationByKey($input->getArgument(self::ARGUMENT_KEY));

        $this->setPhretsSession((new PHRETS\SessionBuilder())->fromConfigurationArray($mlsConfigurationArray))
             ->setResourceAlias($input->getArgument(self::ARGUMENT_RESOURCE_ALIAS))
             ->initializeResourcesAndClasses($input, $mlsConfigurationArray)
             ->setStandardNames($mlsConfigurationArray['standard_names'] ?? false);
    }

    /**
     * $input and $output will generally be used from the getters, and were set in initialize.
     * they are still in the signature as required by the Command parent class
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->phretsLogin();
        foreach ($this->getResourcesAndClasses()[self::KEY_CLASSES] as $class) {
            $resource =  $this->getResourcesAndClasses()[self::KEY_RESOURCE];
            $output->writeln('Resource: ' . $resource);
            $output->writeln('Class: ' . $class);

            $dataOutput = $this->getOutputStrategy();
            if ($dataOutput !== null) {
                $dataOutput->setMlsKey($input->getArgument(self::ARGUMENT_KEY))
                    ->setResourceName($resource)
                    ->setClassName($class);
            }

            $this->initProgressBar();
            $this->startProgressBar();

            $offset = null;
            do {
                $extras = $this->getQueryExtras($offset);

                $results = $this->getPhretsSession()->Search(
                    $resource,
                    $class,
                    $input->getArgument(self::ARGUMENT_QUERY),
                    $extras
                );

                $this->setProgressBarMaxSteps($results->getTotalResultsCount());

                if ($input->getOption(self::OPTION_COUNT)) {
                    break;
                } elseif ($dataOutput !== null) {
                    $dataOutput->outputResults($results);
                } else {
                    $output->write(var_export($results->toArray(), true));
                }

                $count = count($results);
                $offset += $count;

                $this->advanceProgressBar($count);
            } while ($count >= $input->getOption(self::OPTION_LIMIT));

            $this->finishProgressBar();

            if ($input->getOption(self::OPTION_COUNT)) {
                $output->writeln("\nCount: " . $results->getTotalResultsCount());
            }

            if ($dataOutput !== null) {
                $dataOutput->complete();
            }
            $output->writeln('');
        }
        $this->getPhretsSession()->Disconnect();
        return Command::SUCCESS;
    }

    private function getOutputStrategy(): ?StrategyInterface
    {
        switch ($this->getInput()->getOption(self::OPTION_OUTPUT)) {
            case 'csv':
                return new ListingsCsv();
            default:
                return null;
        }
    }

    private function initProgressBar() : self
    {
    //     if (isset($this->progress_bar)) {
    //         throw new \LogicException('Query resources_and_classes already set.');
    //     }
        $this->progress_bar = new ProgressBar($this->getOutput(), (int)$this->getInput()->getOption(self::OPTION_LIMIT));
        $this->progress_bar->setFormat('very_verbose');
        return $this;
    }

    private function startProgressBar() : self
    {
        if (!$this->getInput()->getOption(self::OPTION_NO_PROGRESS_BAR)) {
            $this->getProgressBar()->start();
        }
        return $this;
    }

    private function setProgressBarMaxSteps(int $maxSteps) : self
    {
        if (!$this->getInput()->getOption(self::OPTION_NO_PROGRESS_BAR)) {
            $this->getProgressBar()->setMaxSteps($maxSteps);
        }
        return $this;
    }

    private function advanceProgressBar(int $count) : self
    {
        if (!$this->getInput()->getOption(self::OPTION_NO_PROGRESS_BAR)) {
            $this->getProgressBar()->advance($count);
        }
        return $this;
    }

    private function finishProgressBar() : self
    {
        if (!$this->getInput()->getOption(self::OPTION_NO_PROGRESS_BAR)) {
            $this->getProgressBar()->finish();
        }
        return $this;
    }

    private function validateOptionsCombinations(InputInterface $input, OutputInterface $output) : self
    {
        if ($input->getOption(self::OPTION_RESOURCE) === null xor $input->getOption(self::OPTION_CLASS) === null) {
            throw new \RuntimeException('If a class is specified, a resource must also be specified, and vice versa');
        }

        if ($input->getOption(self::OPTION_COUNT)) {
            $warningFormat = 'Warning: %s is ignored when ' . self::OPTION_COUNT . ' is present.';
            foreach ([self::OPTION_OFFSET, self::OPTION_LIMIT, self::OPTION_SELECT, self::OPTION_OUTPUT] as $option) {
                if ($input->getParameterOption($option) !== false) { // @todo why doesn't this work as expected?
                    $output->writeln(sprintf($warningFormat, $option));
                }
            }
        }

        return $this;
    }

    private function initializeResourcesAndClasses(InputInterface $input, array $mlsConfigurationArray) : self
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

    private function getQueryExtras(?int $offset): array
    {
        $input = $this->getInput();
        $extras = [
            'Format' => 'COMPACT-DECODED',
            'Limit' => $input->getOption(self::OPTION_LIMIT),
            'Count' => $input->getOption(self::OPTION_COUNT) ? 2 : 1,
            'StandardNames' => $this->isStandardNames() ? 1 : 0,
        ];
        $extras['Offset'] = is_null($offset) ? $input->getOption(self::OPTION_OFFSET) : $offset;
        if (!empty($input->getOption(self::OPTION_SELECT))) {
            $extras['Select'] = $input->getOption(self::OPTION_SELECT);
        }
        return $extras;
    }

    private function getInput(): InputInterface
    {
        if ($this->input === null) {
            throw new \LogicException('Query input has not been set.');
        }

        return $this->input;
    }

    private function setInput(InputInterface $input): self
    {
        if ($this->input !== null) {
            throw new \LogicException('Query input already set.');
        }

        $this->input = $input;

        return $this;
    }

    private function getOutput(): OutputInterface
    {
        if ($this->output === null) {
            throw new \LogicException('Query output has not been set.');
        }

        return $this->output;
    }

    private function setOutput(OutputInterface $output): self
    {
        if ($this->output !== null) {
            throw new \LogicException('Query output already set.');
        }

        $this->output = $output;

        return $this;
    }

    private function getPhretsSession(): \PHRETS\Session
    {
        if ($this->phrets_session === null) {
            throw new \LogicException('Query phretsSession has not been set.');
        }

        return $this->phrets_session;
    }

    private function setPhretsSession(\PHRETS\Session $phretsSession): self
    {
        if ($this->phrets_session !== null) {
            throw new \LogicException('Query phretsSession already set.');
        }

        $this->phrets_session = $phretsSession;

        return $this;
    }

    private function getResourceAlias(): string
    {
        if ($this->resource_alias === null) {
            throw new \LogicException('Query resource_alias has not been set.');
        }

        return $this->resource_alias;
    }

    private function setResourceAlias(string $resource_alias): self
    {
        if ($this->resource_alias !== null) {
            throw new \LogicException('Query resource_alias already set.');
        }

        $this->resource_alias = $resource_alias;

        return $this;
    }

    private function getResourcesAndClasses(): array
    {
        if ($this->resources_and_classes === null) {
            throw new \LogicException('Query resources_and_classes has not been set.');
        }

        return $this->resources_and_classes;
    }

    private function setResourcesAndClasses(array $resources_and_classes): self
    {
        if ($this->resources_and_classes !== null) {
            throw new \LogicException('Query resources_and_classes already set.');
        }

        $this->resources_and_classes = $resources_and_classes;

        return $this;
    }

    private function isStandardNames(): bool
    {
        if ($this->standard_names === null) {
            throw new \LogicException('Query standard_names has not been set.');
        }

        return $this->standard_names;
    }

    private function setStandardNames(bool $standard_names): self
    {
        if ($this->standard_names !== null) {
            throw new \LogicException('Query standard_names already set.');
        }

        $this->standard_names = $standard_names;

        return $this;
    }

    private function getProgressBar(): ProgressBar
    {
        if ($this->progress_bar === null) {
            throw new \LogicException('Query progress_bar has not been set.');
        }

        return $this->progress_bar;
    }
}
