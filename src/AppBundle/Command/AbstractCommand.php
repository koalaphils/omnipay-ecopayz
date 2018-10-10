<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of Abstract Command
 *
 * @author cnonog
 */
abstract class AbstractCommand extends ContainerAwareCommand
{
    /**
     * Get current date formated as string
     *
     * @return  string
     */
    protected function getNow()
    {
        $date = new \DateTime();

        return $date->format('Y-m-d H:i:s');
    }

    protected function write($string, $params, OutputInterface $output, $newLine = false, $withDate = true)
    {
        if ($withDate) {
            $string = "[%s] $string";
            array_unshift($params, $this->getNow());
        }
        $output->write(vsprintf($string, $params), $newLine);
    }

    protected function writeln($string, $params, OutputInterface $output, $withDate = true)
    {
        $this->write($string, $params, $output, true, $withDate);
    }

    protected function writeError(\Exception $e, OutputInterface $output, $trace = [])
    {
        $message = "ERR >> [%s] %s ( %s line %s )";
        $params = [$e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()];
        $this->writeln($message, $params, $output);
        $trace = [];
        if ($e->getPrevious() !== null) {
            $this->writeError($e->getPrevious(), $output, $trace);
        }
        $trace[] = $e->getTraceAsString();
        $this->writeln("\n%s\n", [implode("\n", $trace)], $output, false);
    }
}
