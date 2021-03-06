<?php

namespace Lsw\GettextTranslationBundle\Command;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * ExtractBundleCommand extracts records to be translated from the specified bundle
 *
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 * @author Andrii Shchurkov <a.shchurkov@leaseweb.com>
 */
class ExtractBundleCommand extends AbstractCommand
{
    /**
     * Configures extractor
     *
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('gettext:bundle:extract')
            ->setDescription('Extract translations from a bundle')
            ->setDefinition(array(
                new InputArgument('bundle', InputArgument::REQUIRED, 'The bundle'),
                new InputOption('keep-cache', null, InputOption::VALUE_NONE, 'Do not delete the intermediate twig.cache.php file'),
            ))
            ->setHelp(<<<EOT
The <info>gettext:bundle:extract</info> command extracts translations from a bundle:

  <info>php app/console gettext:bundle:extract</info>

This interactive shell will first ask you for a bundle name.

You can alternatively specify the bundle as the first argument:

  <info>php app/console gettext:bundle:extract FOSUserBundle</info>

You can keep the intermediate twig.cache.php file by specifying the keep-cache flag:

  <info>php app/console gettext:bundle:extract FOSUserBundle --keep-cache</info>

EOT
            );
    }

    /**
     * Execute method get an input texts prepare it for each locale
     *
     * @param InputInterface $input Input interface
     * @param OutputInterface $output Output interface
     *
     * @return int
     * @throws Exception
     * @see Command
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $root = $this->getContainer()->getParameter('kernel.root_dir');
        chdir("$root/..");
        $bundle = $input->getArgument('bundle');
        $bundle = ltrim($bundle, '@');
        $bundleObj = $this->getContainer()->get('kernel')->getBundle($bundle);
        if (!$bundleObj) {
            throw new ResourceNotFoundException("Cannot load bundle resource '$bundle'");
        }

        $path = $bundleObj->getPath().'/Resources/gettext/messages.pot';
        $twig = $bundleObj->getPath().'/Resources/gettext/twig.cache.php';
        $results = $this->convertTwigToPhp($twig, $bundle);
        foreach ($results as $filename => $status) {
            $output->writeln("$status: $filename");
        }
        $results = $this->extractFromPhp($path);
        foreach ($results as $filename => $status) {
            $output->writeln("$status: $filename");
        }
        if (!$input->getOption('keep-cache')) {
            unlink($twig);
        }

        return 0;
    }

    /**
     * Method returns list of languages
     *
     * @param InputInterface  $input  Input interface
     * @param OutputInterface $output Output interface
     *
     * @see Command
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('bundle')) {
            $questionHelper = $this->getHelper('question');
            $bundleQuestion = new Question('Please give the bundle: ');
            $bundleQuestion->setValidator(function ($bundle) {
                if (empty($bundle)) {
                    throw new RuntimeException(
                        'Bundle can not be empty'
                    );
                }

                return $bundle;
            });

            $bundle = $questionHelper->ask($input, $output, $bundleQuestion);
            $input->setArgument('bundle', $bundle);
        }
    }
}