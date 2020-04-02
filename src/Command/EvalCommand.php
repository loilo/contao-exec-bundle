<?php namespace Loilo\ContaoExecBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Psy\Shell;
use Psy\Configuration;

use Lowlight\Lowlight;

class EvalCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('contao:eval')
            ->setDescription('Evaluates Contao-related PHP code and prints the result')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command evaluates a PHP expression or program from which Contao features can be called.
It's essentially like calling <fg=yellow>debug:repl</> with one single input and a predefined output format.

EOT
            )

            ->addArgument(
                'program',
                InputArgument::REQUIRED,
                'The PHP code to execute'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                <<<EOT
The output format of the evaluated result.
Possible values:
<fg=yellow>dump</>     Uses Symfony's <fg=yellow>VarDumper</> for human-readable results
<fg=yellow>plain</>    Uses PHP <fg=yellow>echo</> without any sophisticated serialization
<fg=yellow>export</>   Uses PHP's <fg=yellow>var_export()</>
<fg=yellow>json</>     Prints result serialized as JSON
EOT
                ,
                'dump',
                function ($value) {
                    return in_array($value, [ 'dump', 'plain', 'export', 'json' ]);
                }
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $this->getApplication()->setCatchExceptions(false);

        $shouldHighlight = $output->isDecorated();
        $program = $input->getArgument('program');
        $format = $input->getOption('format');

        // Configure shell
        $config = new Configuration([
            'updateCheck' => 'never',
            'errorLoggingLevel' => $this->errorReportingLevel,
            'colorMode' => $shouldHighlight
                ? Configuration::COLOR_MODE_FORCED
                : Configuration::COLOR_MODE_DISABLED
        ]);

        error_reporting($this->errorReportingLevel);

        $shell = new Shell($config);
        $this->loadDatabaseHelper($shell);

        // Evaluate program
        try {
            $result = $shell->execute($program, true);

            $ll = new Lowlight();

            // Format output
            switch ($format) {
                case 'dump':
                    $presenter = $config->getPresenter();
                    $this->configureCasters($presenter);

                    $output->writeln($presenter->present($result));
                    break;

                case 'plain':
                    $output->writeln($result);
                    break;

                case 'export':
                    $result = var_export($result, true);
                    if ($shouldHighlight) {
                        $output->writeln($ll->highlight('php', $result)->value);
                    } else {
                        $output->writeln($result);
                    }
                    break;

                case 'json':
                    $result = json_encode(
                        $result,
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    );

                    if ($shouldHighlight) {
                        $output->writeln($ll->highlight('json', $result)->value);
                    } else {
                        $output->writeln($result);
                    }
                    break;
            }
        } catch (\Exception $e) {
            // Select output channel
            if ($output instanceof ConsoleOutputInterface) {
                $stderr = $output->getErrorOutput();
            } else {
                $stderr = $output;
            }

            // Print error just like PHP would
            $stderr->writeln([
                sprintf(
                    'Fatal error: Uncaught %s: %s in %s:%s',
                    get_class($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ),
                'Stack trace:',
                $e->getTraceAsString()
            ]);

            return 1;
        } finally {
            return 0;
        }
    }
}
