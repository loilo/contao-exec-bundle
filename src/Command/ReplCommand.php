<?php namespace Loilo\ContaoExecBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Psy\Shell;
use Psy\Configuration;

class ReplCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('debug:repl')
            ->setDescription('Opens a REPL to run Contao-related PHP code')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command starts a PHP REPL from which Contao features can be called (powered by <fg=yellow>PsySH</>).
See https://github.com/bobthecow/psysh/wiki for more information.

EOT
            )
            ->addOption(
                'prepend',
                'p',
                InputOption::VALUE_REQUIRED,
                'Code to execute before opening the REPL'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $_output)
    {
        parent::execute($input, $_output);

        // Start Shell
        $config = new Configuration([
            'updateCheck' => 'never',
            'prompt' => '>',
            'startupMessage' => '',
            'errorLoggingLevel' => $this->errorReportingLevel,
            'useBracketedPaste' => in_array('readline', get_loaded_extensions())
        ]);

        $this->configureCasters($config->getPresenter());

        $shell = new Shell($config);
        $this->loadDatabaseHelper($shell);

        if ($input->getOption('prepend')) {
            $shell->execute($input->getOption('prepend'));
        }

        try {
            $shell->run();
        } finally {
            return 0;
        }
    }
}
