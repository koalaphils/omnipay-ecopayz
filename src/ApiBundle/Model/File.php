<?php

namespace ApiBundle\Model;

class File
{
    private $files;

    public function getFiles()
    {
        return $this->files;
    }

    public function setFiles($files): self
    {
        $this->files = $files;

        return $this;
    }
}
