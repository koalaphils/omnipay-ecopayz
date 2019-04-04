<?php

namespace ThemeBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ThemeApplyCommand extends ContainerAwareCommand
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    protected function configure()
    {
        $this
            ->setName('theme:apply')
            ->setDescription('Apply the theme')
            ->setDefinition([
                new InputArgument('theme', InputArgument::OPTIONAL, 'The theme to be dump', null),
                new InputArgument('target', InputArgument::OPTIONAL, 'The target asset directory', 'web'),
            ])
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Remove theme')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $theme = 'default';

        if (!is_null($input->getArgument('theme'))) {
            $theme = $input->getArgument('theme');
        } elseif ($this->getContainer()->hasParameter('theme')) {
            $theme = $this->getContainer()->getParameter('theme');
        }

        $this->filesystem = $this->getContainer()->get('filesystem');

        $themePath = $this->getContainer()->getParameter('kernel.root_dir') . '/../themes/' . $theme;
        foreach ($this->getContainer()->get('kernel')->getBundles() as $name => $bundle) {
            $origPaths = [
                #'view' => "$themePath/$name/views",
                'public' => "$themePath/$name/public",
                'translations' => "$themePath/$name/translations",
            ];
            $targetDirs = [
                #'view' => $bundle->getPath() . '/Resources/views',
                'public' => $bundle->getPath() . '/Resources/public',
                'translations' => $bundle->getPath() . '/Resources/translations',
            ];

            foreach ($origPaths as $type => $path) {
                if (!is_dir($path)) {
                    continue;
                }
                if ($input->getOption('remove')) {
                    $this->removeSymLink($targetDirs[$type]);
                } else {
                    try {
                        $this->symlink($path, $targetDirs[$type], true);
                    } catch (\Exception $e) {
                        $this->copyFile($path, $targetDirs[$type]);
                    }
                    
                }
            }
        }

        if (is_dir($dir = "$themePath/assets")) {
            if ($input->getOption('remove')) {
                $this->removeSymLink($input->getArgument('target') . '/assets');
            } else {
                try {
                    $this->symlink($dir, $input->getArgument('target') . '/assets', true);
                } catch (\Exception $e) {
                    $this->copyFile($dir, $input->getArgument('target') . '/assets');
                }
                
            }
        }
    }

    /**
     * Creates symbolic link.
     *
     * @param string $originDir
     * @param string $targetDir
     * @param bool   $relative
     *
     * @throws IOException if link can not be created
     */
    private function symlink(string $originDir, string $targetDir, bool $relative = false)
    {
        if ($relative) {
            $originDir = $this->filesystem->makePathRelative($originDir, realpath(dirname($targetDir)));
        }
        $this->filesystem->symlink($originDir, $targetDir);
        if (!file_exists($targetDir)) {
            throw new IOException(sprintf('Symbolic link "%s" was created but appears to be broken.', $targetDir), 0, null, $targetDir);
        }
    }

    private function removeSymLink(string $target)
    {
        $this->filesystem->remove([
            $target,
        ]);
    }

    private function copyFile(string $originDir, string $targetDir)
    {
        $this->filesystem->mirror($originDir, $targetDir);
    }
}
