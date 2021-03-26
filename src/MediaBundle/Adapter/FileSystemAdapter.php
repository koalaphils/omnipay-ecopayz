<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MediaBundle\Adapter;


use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * FileSystemAdapter provides a means to perform basic operations on filesystem
 *
 * @author Melvin D. Protacio<melvin.protacio@zmtsys.com>
 */
class FileSystemAdapter extends AbstractFileStorage
{
    public function getFilesInFolder(?string $folder = null, array $filters = []): array
    {
        if($folder === null || trim($folder) === ''){
            $folder = array_get($this->options, 'path', $this->defaultUploadFolder);
        }
        $fullPath = $this->getPath($folder);
        $finder = new Finder();

        if (array_has($filters, 'search')) {
            $finder->depth('== 0')->in($fullPath)->name('*' . $filters['search'] . '*');
        }
        if (empty($filters)) {
            $finder->depth('== 0')->files()->in($fullPath);
        }
        $files = [];
        foreach ($finder as $file) {
            if ($file->isFile()) {
                $pathInfo = pathinfo($file->getRealPath());
                $files[] = [
                    'filename' => $pathInfo['basename'],
                    'folder' => $this->getDirectory($file->getRealPath()),
                    'route' => [
                        'render' => $this->router->generate('app.render_file', ['fileName' => $pathInfo['basename'], 'folder' => $this->getDirectory($file->getRealPath())]),
                        'delete' => $this->router->generate('media.file_delete', ['fileName' => $pathInfo['basename'], 'folder' => $this->getDirectory($file->getRealPath())]),
                    ],
                    'ext' => $pathInfo['extension'],
                    'size' => $file->getSize(),
                    'lastModified' => date('Y-m-d H:i:s', $file->getMTime()),
                    'basename' => $pathInfo['filename'],
                ];
            }
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
    public function getFile(string $fileName, bool $info = false): array
    {
       $fileName = $this->getFilePath($fileName);

        if (!($fileName instanceof File)) {
            $file = new File($fileName, true);
        } else {
            $file = $fileName;
        }

        $fileInfo = pathinfo($file->getRealPath());
        $retval = [
                'ext' => $fileInfo['extension'],
                'filename' => $fileInfo['basename'],
                'folder' => $this->getDirectory($fileName),
                'size' => $file->getSize(),
                'lastModified' => gmdate('Y-m-d\TH:i:s\Z', $file->getMTime()),
                'basename' => $fileInfo['filename'],
                'storage' => 'FS'
                ];
        if (!$info) {
            return $retval;
        }

        $fileInfo = [
            'filename' => $fileInfo['basename'],
            'folder' => $this->getDirectory($file->getRealPath()),
            'route' => [
                'render' => $this->router->generate('app.render_file', ['fileName' => $fileInfo['basename'], 'folder' => $this->getDirectory($file->getRealPath())]),
                'delete' => $this->router->generate('media.file_delete', ['fileName' => $fileInfo['basename'], 'folder' => $this->getDirectory($file->getRealPath())]),
            ],
            'ext' => $fileInfo['extension'],
            'size' => $file->getSize(),
            'lastModified' => gmdate('Y-m-d\TH:i:s\Z', $file->getMTime()),
            'basename' => $fileInfo['filename'],
        ];

        return $fileInfo;
    }

    public function renameFile(string $fileName, string $rename): array
    {
        try {
            $fileName = $this->getFilePath($fileName);
            $rename = pathinfo($rename);

            if ($this->isFileExists($fileName)) {
                $pathInfo = pathinfo($fileName);
                $actualFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $rename['basename'] . ".${pathInfo['extension']}";
                $i = 1;
                while ($this->isFileExists($actualFile)) {
                    $actualFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $rename['basename'] . "(${i}).${pathInfo['extension']}";
                }
                $pathInfo = pathinfo($actualFile);
                $file = new File($fileName);
                $fileInfo = [
                    'filename' => $pathInfo['basename'],
                    'route' => [
                        'render' => $this->router->generate('app.render_file', ['fileName' => $pathInfo['basename'], 'folder' => $this->getDirectory($actualFile)]),
                        'delete' => $this->router->generate('media.file_delete', ['fileName' => $pathInfo['basename'], 'folder' => $this->getDirectory($actualFile)]),
                    ],
                    'ext' => $pathInfo['extension'],
                    'size' => $file->getSize(),
                    'lastModified' => $file->getMTime(),
                    'basename' => $pathInfo['filename'],
                ];
                $file->move($pathInfo['dirname'], $pathInfo['basename']);
                $status = ['success' => true, 'file' => $fileInfo, 'code' => 200];
            }
        } catch (\Exception $e) {
            $status = ['success' => false, 'error' => $e->getMessage(), 'code' => 400];
        }

        return $status;
    }


    public function deleteFile(string $file, bool $base = false): array
    {
        try {
            $fileInfo = $this->getFile($file);
            $this->backend->remove($this->getFilePath($file, $base));

            return array_merge($fileInfo, ['success' => true, 'code' => 200]);
        } catch (IOExceptionInterface $e) {
            return array_merge($fileInfo, ['success' => false, 'error' => $e->getMessage(), 'code' => $e->getCode()]);
        }
    }

    public function createFile(string $file, bool $base = false): array
    {
        try {
            $this->backend->touch($this->getFilePath($file, $base));

            return ['success' => true];
        } catch (IOExceptionInterface $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function isFileExists(string $filename): bool
    {
        return is_file($this->getFilePath($filename));
    }

    public function getFileUri(string $fileName, ?string $folder = null, bool $presigned = true): string
    {
        $filePath = parent::getFileUri($this->trimPrefixPath($fileName), $folder, $presigned);

        return "file://${filePath}";
    }

    public function getContents(string $fileName): string
    {
        return file_get_contents($fileName);
    }

    public function putContents(string $fileName, string $data, ?array $options = [])
    {
        return file_put_contents($fileName, $data);
    }
}
