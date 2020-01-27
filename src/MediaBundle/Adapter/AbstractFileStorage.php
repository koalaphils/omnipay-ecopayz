<?php

namespace MediaBundle\Adapter;


use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Imagick;
use ImagickException;
use Symfony\Component\Cache\Adapter\AbstractAdapter as CacheAdapter;

abstract class AbstractFileStorage
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem|\Aws\S3\S3Client
     */
    protected $backend;
    protected $options;
    protected $router;
    protected $defaultUploadFolder = '';
    /**
     * @var CacheAdapter
     */
    protected $cache;

    /**
     * @param \Symfony\Component\Filesystem\Filesystem|\Aws\S3\S3Client $backend
     */
    public function __construct($backend, Router $router, string $defaultUploadFolder, ?array $options = null){
        $this->backend = $backend;
        $this->options = $options;
        $this->router = $router;
        $this->defaultUploadFolder = $defaultUploadFolder;
        $this->cache = array_get($options, 'cache', null);
    }

    public function getFilePath(string $file, bool $base = false): string
    {
        $file = $this->trimPrefixPath($file);
        if (!$base) {
            return rtrim(array_get($this->options, 'path', $this->defaultUploadFolder), " \t\n\r\0\x0B\/") . DIRECTORY_SEPARATOR . trim($file, " \t\n\r\0\x0B\/");
        }

        return $file;
    }

    public function getFileUri(string $fileName, ?string $folder = null, bool $presigned = true): string
    {
        $folder = $this->getPath($folder);
        $filePath = $this->getFilePath($folder . $this->trimPrefixPath($fileName));

        //attempt to check/upload if the file exists and is in S3
        $this->getFile($filePath);

        return $filePath;
    }

    protected function trimPrefixPath(string $fileName, ?string $prefix = null): string
    {
        $fileName = rtrim($fileName, " \t\n\r\0\x0B\/");
        $prefix = rtrim($prefix ?? array_get($this->options, 'path', $this->defaultUploadFolder), " \t\n\r\0\x0B\/");
        if(strpos($fileName, $prefix) === 0){
            return ltrim(substr($fileName, strlen($prefix)), " \t\n\r\0\x0B\/");
        }
        return $fileName;
    }

    public function getPath(?string $folder = null): string
    {
        $folder = $this->trimPrefixPath($folder ?? '');
        if ($folder !== '' && $folder !== null) {
            $folder = trim($folder, " \t\n\r\0\x0B\/");

            return rtrim(array_get($this->options, 'path', $this->defaultUploadFolder), " \t\n\r\0\x0B\/") . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
        }

        return rtrim(array_get($this->options, 'path', $this->defaultUploadFolder), '/') . DIRECTORY_SEPARATOR;
    }

    public function getDirectory(string $filename): string
    {
        $pathInfo = pathinfo($filename);

        return $this->trimPrefixPath($pathInfo['dirname']);
    }

    abstract public function getFilesInFolder(?string $folder = null, array $filters = []): array;

    abstract public function getFile(string $fileName, bool $info = false): array;

    abstract public function renameFile(string $fileName, string $rename): array;

    public function uploadRawFile(UploadedFile $file, string $folder, ?string $filename = null): array
    {
        $folder = $this->getPath($folder);
        $filepath = $this->getFilePath($folder . (empty($filename) ? $file->getClientOriginalName() : $filename));
        $pathInfo = pathinfo($filepath);
        $actualPath = $filepath;
        $i = 1;
        while($this->isFileExists($actualPath)){
            $actualPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . "(${i})." . $pathInfo['extension'];
            $i++;
        }
        $this->putContents($actualPath, file_get_contents($file->getRealPath()));

        return $this->getFile($actualPath);
    }

    public function compressUploadFile(UploadedFile $file, string $folder, ?string $filename = null): array
    {
        try {
            $uploadedFile = new Imagick($file->getRealPath());

            $resolution = $uploadedFile->getImageGeometry();

            if($resolution['width'] > 1920 || $resolution['height'] > 1080){
                $uploadedFile->adaptiveResizeImage(1920, 1080, true);
            }

            $folder = $this->getPath($folder);
            $filepath = $this->getFilePath($folder . (empty($filename) ? $file->getClientOriginalName() : $filename));
            $pathInfo = pathinfo($filepath);
            $actualPath = $filepath;

            $tmpFile = tmpfile();
            $uploadedFile->writeImageFile($tmpFile);
            fseek($tmpFile, 0);

            $i = 1;
            while($this->isFileExists($actualPath)){
                $actualPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . "(${i})." . $pathInfo['extension'];
                $i++;
            }
            $this->putContents($actualPath, stream_get_contents($tmpFile));

            fclose($tmpFile);
            $uploadedFile->clear();
            $uploadedFile->destroy();
            return $this->getFile($actualPath);
        } catch (ImagickException $e) {
            throw new ImagickException($e->getMessage(), $e->getCode());
        }
    }

    abstract public function deleteFile(string $file, bool $base = false): array;

    abstract public function createFile(string $file, bool $base = false): array;

    abstract public function isFileExists(string $filename): bool;

    public function checkIfFileInUploadFolder(string $uploadFolder, string $relativePath): bool
    {
        $fileRealPath = realpath($relativePath);
        $explodedFolder = explode(DIRECTORY_SEPARATOR, $uploadFolder);
        $explodedRealPath = explode(DIRECTORY_SEPARATOR, $fileRealPath, count($explodedFolder));

        $uploadDirPath = implode(DIRECTORY_SEPARATOR, $explodedFolder);
        $filePath = implode(DIRECTORY_SEPARATOR, $explodedRealPath);
        $beginningOfString = 0;

        return (strpos($filePath, $uploadDirPath) === $beginningOfString);
    }

    abstract public function getContents(string $fileName): string;

    abstract public function putContents(string $fileName, string $data, ?array $options = []);
}
