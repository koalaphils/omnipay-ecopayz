<?php

namespace ThemeBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ThemeCreateCommand extends ContainerAwareCommand
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    protected function configure()
    {
        $this
            ->setName('theme:create')
            ->setDescription('Create Theme')
            ->addArgument('theme', InputArgument::REQUIRED, 'Theme Name')
            ->addArgument('copyTheme', InputArgument::OPTIONAL, 'Copy Theme')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $theme = $input->getArgument('theme');
        $this->filesystem = $this->getContainer()->get('filesystem');

        if ($input->getArgument('copyTheme')) {
            $copyTheme = $input->getArgument('copyTheme');
            if (is_dir($this->getThemePath($copyTheme))) {
                $this->copy($theme, $copyTheme);
            } else {
                throw new \RuntimeException("$copyTheme is not directory");
            }
        } else {
            $this->generateNewTheme($theme);
        }
    }

    private function generateNewTheme($theme)
    {
        foreach ($this->getContainer()->get('kernel')->getBundles() as $name => $bundle) {
            if (method_exists($bundle, 'registerThemetViews')) {
                foreach ($bundle->registerThemetViews() as $file) {
                    $this->filesystem->dumpFile($this->getThemePath($theme) . "/" . $name . "/views/" . $file, '');
                }
            }
        }

        $this->filesystem->mkdir($this->getThemePath($theme) . '/assets');
    }

    private function copy($theme, $copyTheme)
    {
        $this->filesystem->copy($this->getThemePath($copyTheme), $this->getThemePath($theme));
    }

    private function getThemePath($theme)
    {
        return $this->getContainer()->getParameter('kernel.root_dir') . '/../themes/' . $theme;
    }
}
