<?php

namespace MediaBundle\Command;

use MediaBundle\Adapter\FileSystemAdapter;
use MediaBundle\Adapter\S3Adapter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SyncFStoS3Command extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('media:sync-fs-to-s3')
            ->setDescription('Attempts to upload files to S3 from the filesystem.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystemAdapter = new FileSystemAdapter(new Filesystem(), $this->getContainer()->get('router'), '/uploads/', [
            'path' => $this->getContainer()->getParameter('upload_folder'),
        ]);
        //Assuming mediaManager is set to use S3
        $mediaManager = $this->getContainer()->get('media.manager');

        $finder = new Finder();
        $finder->files()->followLinks()->in($this->getContainer()->getParameter('upload_folder'));

        foreach ($finder as $file) {
            $directory = $filesystemAdapter->getDirectory($file->getRelativePathname());
            $filename = $file->getFilename();
            try{
                if(!$mediaManager->isFileExists($directory.$filename) && $mediaManager->getFileUri($filename, $directory)){
                    $output->writeln("Uploaded file $filename on folder $directory.");
                }else{
                    $output->writeln("Failed to upload file $filename on $directory.");
                }
            }catch(\Exception $ex){
                $output->writeln($ex->getMessage());
            }
        }
    }
}
