<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MediaBundle\Manager;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Finder\Finder;
use AppBundle\Manager\AbstractManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Description of MediaManager.
 *
 * @author cnonog
 */
class MediaManager extends AbstractManager
{
    /**
     * @var string
     */
    private $path;

    public function init()
    {
        $this->path = $this->container->getParameter('upload_folder');
    }

    public function getFiles($filter = [])
    {
        $finder = new Finder();
        if (array_has($filter, 'search')) {
            $finder->depth('== 0')->in($this->path)->name('*' . $filter['search'] . '*');
        }
        if (empty($filter)) {
            $finder->depth('== 0')->files()->in($this->path);
        }
        $files = [];

        /** @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($finder as $file) {
            $len = strlen($file->getFileName()) - strlen($file->getExtension()) - 1;
            $files[] = [
                'filename' => $file->getFileName(),
                'route' => [
                    'render' => $this->getRouter()->generate('app.render_file', ['fileName' => $file->getFileName()]),
                    'delete' => $this->getRouter()->generate('media.file_delete', ['fileName' => $file->getFileName()]),
                ],
                'ext' => $file->getExtension(),
                'size' => $file->getSize(),
                'lastModified' => date('Y-m-d H:i:s', $file->getMTime()),
                'basename' => substr($file->getFileName(), 0, $len),
            ];
        }

        return $files;
    }

    public function getFilesInFolder(string $folder, array $filters = []): array
    {
        $fullPath = $this->getFilePath($folder);
        $finder = new Finder();

        if (array_has($filters, 'search')) {
            $finder->depth('== 0')->in($fullPath)->name('*' . $filters['search'] . '*');
        }
        if (empty($filters)) {
            $finder->depth('== 0')->files()->in($fullPath);
        }
        $files = [];

        foreach ($finder as $file) {
            $len = strlen($file->getFileName()) - strlen($file->getExtension()) - 1;
            $files[] = [
                'filename' => $file->getFileName(),
                'folder' => $folder,
                'route' => [
                    'render' => $this->getRouter()->generate('app.render_file', ['fileName' => $file->getFileName(), 'folder' => $folder]),
                    'delete' => $this->getRouter()->generate('media.file_delete', ['fileName' => $file->getFileName(), 'folder' => $folder]),
                ],
                'ext' => $file->getExtension(),
                'size' => $file->getSize(),
                'lastModified' => date('Y-m-d H:i:s', $file->getMTime()),
                'basename' => substr($file->getFileName(), 0, $len),
            ];
        }

        return $files;
    }

    /**
     * Get file.
     *
     * @param type $fileName
     * @param type $info
     *
     * @return array
     */
    public function getFile($fileName, $info = false)
    {
        $folders = explode('/', $fileName, -1);

        if (!($fileName instanceof File)) {
            $file = new File(rtrim($this->path, '/') . '/' . ltrim($fileName), true);
        } else {
            $file = $fileName;
        }

        if (!$info) {
            return $file;
        }

        $len = strlen($file->getFileName()) - strlen($file->getExtension()) - 1;
        $fileInfo = [
            'filename' => $file->getFileName(),
            'folder' => implode('/', $folders),
            'route' => [
                'render' => $this->getRouter()->generate('app.render_file', ['fileName' => $file->getFileName(), 'folder' => implode('/', $folders)]),
                'delete' => $this->getRouter()->generate('media.file_delete', ['fileName' => $file->getFileName()]),
            ],
            'ext' => $file->getExtension(),
            'size' => $file->getSize(),
            'lastModified' => $file->getMTime(),
            'basename' => substr($file->getFileName(), 0, $len),
        ];

        return $fileInfo;
    }

    public function renameFile($fileName, $rename)
    {
        try {
            $file = new File(rtrim($this->path, '/') . '/' . ltrim($fileName), true);
            $folder = explode('/', trim($rename, " \t\n\r\0\x0B\/"));
            $rename = $folder[count($folder) - 1];
            unset($folder[count($folder) - 1]);
            $folder = implode('/', $folder);

            $origName = $rename . '.' . $file->getExtension();
            $i = 1;
            $fs = new Filesystem();
            $status = [];
            do {
                $loop = true;
                if ($fs->exists($this->getPath($folder) . $origName)) {
                    $origName = $rename . "($i)." . $file->getExtension();
                    ++$i;
                } else {
                    $loop = false;
                    $file->move($this->getPath($folder), $origName);
                    $file = new File($this->getPath($folder) . $origName, true);
                    $len = strlen($file->getFileName()) - strlen($file->getExtension()) - 1;
                    $fileInfo = [
                        'filename' => $file->getFileName(),
                        'route' => [
                            'render' => $this->getRouter()->generate('app.render_file', ['fileName' => $file->getFileName()]),
                            'delete' => $this->getRouter()->generate('media.file_delete', ['fileName' => $file->getFileName()]),
                        ],
                        'ext' => $file->getExtension(),
                        'size' => $file->getSize(),
                        'lastModified' => $file->getMTime(),
                        'basename' => substr($file->getFileName(), 0, $len),
                    ];
                    $status = ['success' => true, 'file' => $fileInfo, 'code' => 200];
                }
            } while ($loop);
        } catch (\Exception $e) {
            $status = ['success' => false, 'error' => $e->getMessage(), 'code' => 400];
        }

        return $status;
    }

    /**
     * @param UploadedFile $file
     * @param string       $folder
     *
     * @return array
     */
    public function uploadFile($file, $folder = '')
    {
        $origName = $file->getClientOriginalName();
        $i = 1;
        $fs = new Filesystem();
        $status = [];
        do {
            $loop = true;
            try {
                if ($fs->exists($this->path . $folder . '/' . $origName)) {
                    $len = strlen($file->getClientOriginalName()) - strlen($file->getClientOriginalExtension()) - 1;
                    $origName = substr($file->getClientOriginalName(), 0, $len) . "($i)." . $file->getClientOriginalExtension();
                    ++$i;
                } else {
                    $loop = false;
                    $file->move($this->path . $folder, $origName);
                    $status = ['success' => true, 'filename' => $origName, 'folder' => $folder, 'code' => 200];
                }
            } catch (\Exception $e) {
                $loop = false;
                $status = ['success' => false, 'error' => $e->getMessage(), 'code' => $e->getCode()];
            }
        } while ($loop);

        return $status;
    }

    public function deleteFile($file, $base = false)
    {
        $fs = new Filesystem();
        try {
            if (!$base) {
                $fs->remove(rtrim($this->path, " \t\n\r\0\x0B\/") . '/' . trim($file, " \t\n\r\0\x0B\/"));
            } else {
                $fs->remove($file);
            }

            return ['success' => true, 'code' => 200];
        } catch (IOExceptionInterface $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'code' => $e->getCode()];
        }
    }

    public function createFile($file, $base = false)
    {
        $fs = new Filesystem();
        try {
            if (!$base) {
                $fs->touch(rtrim($this->path, " \t\n\r\0\x0B\/") . '/' . trim($file, " \t\n\r\0\x0B\/"));
            } else {
                $fs->touch($file);
            }

            return ['success' => true];
        } catch (IOExceptionInterface $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getFilePath($file, $base = false): string
    {
        if (!$base) {
            return rtrim($this->path, " \t\n\r\0\x0B\/") . '/' . trim($file, " \t\n\r\0\x0B\/");
        }

        return $file;
    }

    public function getPath($folder = '')
    {
        if ($folder !== '' && $folder !== null) {
            $folder = trim($folder, " \t\n\r\0\x0B\/");

            return rtrim($this->path, " \t\n\r\0\x0B\/") . '/' . $folder . '/';
        }

        return $this->path;
    }

    public function isFileExists(string $filename): bool
    {
        return is_file($this->getFilePath($filename));
    }

    public function renderFile(string $filename, ?string $folder = '')
    {
        $folder = trim($folder, '/');
        $uploadFolder = rtrim($this->getParameter('upload_folder'), '/');
        $pathInfo = [
            $uploadFolder,
            $folder,
            trim($filename, '/'),
        ];

        $pathValid = [];
        foreach ($pathInfo as $path) {
            if ($path !== '') {
                $pathValid[] = $path;
            }
        }

        $relativePath = implode('/', $pathValid);
        if (!$this->checkIfFileInUploadFolder($uploadFolder, $relativePath)) {
            throw new FileNotFoundException($filename);
        }

        return $relativePath;
    }

    private function checkIfFileInUploadFolder($uploadFolder, $relativePath): bool
    {
        $fileRealPath = realpath($relativePath);
        $explodedFolder = explode(DIRECTORY_SEPARATOR, $uploadFolder);
        $explodedRealPath = explode(DIRECTORY_SEPARATOR, $fileRealPath, count($explodedFolder));

        $uploadDirPath = implode(DIRECTORY_SEPARATOR, $explodedFolder);
        $filePath = implode(DIRECTORY_SEPARATOR, $explodedRealPath);
        $beginningOfString = 0;

        return  (strpos($filePath, $uploadDirPath) === $beginningOfString);
    }

    protected function getRepository()
    {
    }
}
