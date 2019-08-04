<?php namespace Loilo\ContaoExecBundle\Command;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Loilo\ContaoExecBundle\Util\Caster;
use Psy\Shell;
use Psy\VarDumper\Presenter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var int
     */
    protected $errorReportingLevel;

    /**
     * @var ContaoFrameworkInterface
     */
    protected $contao;

    public function __construct(string $rootDir, ContaoFrameworkInterface $contao)
    {
        parent::__construct();

        $this->rootDir = $rootDir;
        $this->contao = $contao;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption(
                'from-cwd',
                'c',
                InputOption::VALUE_NONE,
                'Execute the REPL in the current working directory instead of the project root'
            )
            ->addOption(
                'no-framework',
                null,
                InputOption::VALUE_NONE,
                'Prevent the Contao framework from being initialized'
            )
            ->addOption(
                'error-reporting',
                'r',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Define the error levels which should be reported',
                [ 'E_ERROR', 'E_PARSE', 'E_WARNING' ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $_output)
    {
        // Prevent framework initialization
        if (!$input->getOption('no-framework')) {
            $this->contao->initialize();
        }

        // Execute from CWD
        if (!$input->getOption('from-cwd')) {
            chdir($this->rootDir);
        }

        // Set reporting level
        $this->errorReportingLevel = array_reduce(array_map(function ($levelName) {
            return constant($levelName);
        }, $input->getOption('error-reporting')), function ($carry, $current) {
            return $carry | $current;
        }, 0);
    }

    /**
     * Configure casters on a shell presenter
     *
     * @param Presenter $presenter The presenter to configure
     * @return void
     */
    protected function configureCasters(Presenter $presenter)
    {
        $presenter->addCasters([
            \Contao\Model::class => Caster::class . '::castModel',
            \Illuminate\Support\Collection::class => Caster::class . '::castCollection'
        ]);
    }

    /**
     * Load the db() helper function into the shell's namespace
     * (if the loilo/contao-illuminate-database package is installed)
     *
     * @param Shell $shell The shell to load the helper into
     * @return void
     */
    protected function loadDatabaseHelper(Shell $shell)
    {
        $dbFQFN = 'Loilo\\ContaoIlluminateDatabaseBundle\\Database\\db';
        if (function_exists($dbFQFN)) {
            $shell->execute("use $dbFQFN;");
        }
    }
}
