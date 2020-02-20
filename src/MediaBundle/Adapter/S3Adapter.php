<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MediaBundle\Adapter;

use DateInterval;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;
/**
 * S3Adapter provides the functionality to store and retrieve files from AWS S3
 *
 * @author Melvin D. Protacio<melvin.protacio@zmtsys.com>
 */
class S3Adapter extends AbstractFileStorage
{
    private function uploadNonExistingFile($fullpath, ?string $folder = null): void
    {
        $filesystemAdapter = new FileSystemAdapter(new Filesystem(), $this->router, '/uploads/',
            [
                'path' => DIRECTORY_SEPARATOR. array_get($this->options, 'path', $this->defaultUploadFolder),
            ]);
        $relativePath = $this->trimPrefixPath($fullpath, array_get($this->options, 'path', $this->defaultUploadFolder));
        $fsFullPath = $filesystemAdapter->getFilePath($relativePath);
        if($filesystemAdapter->isFileExists($fsFullPath)){
            $this->putContents($fullpath, $filesystemAdapter->getContents($fsFullPath));
        }else{
            throw new FileNotFoundException($fullpath);
        }
    }

    public function getFilesInFolder(?string $folder = null, array $filters = []): array
    {
        if($folder === null || trim($folder) === ''){
            $folder = array_get($this->options, 'path', $this->defaultUploadFolder);
        }

        $fullPath = $this->getPath($folder);

        $iterator = $this->backend->getIterator('ListObjectsV2', [
            'Bucket' => array_get($this->options, 'bucket', null),
            'Prefix' => $fullPath
        ]);
        $files = [];

        foreach ($iterator as $object){
            $objectPathInfo = pathinfo($object['Key']);

            if (strcasecmp($objectPathInfo['dirname'] . DIRECTORY_SEPARATOR, $fullPath) === 0 && (array_get($filters, 'search', true) === true || stripos($object['Key'], array_get($filters, 'search')) > -1))
            {
                $files[] = [
                    'filename' => $objectPathInfo['basename'],
                    'folder' => $this->getDirectory($object['Key']),
                    'route' => [
                        'render' => $this->router->generate('app.render_file', ['fileName' => $objectPathInfo['basename'], 'folder' => $this->getDirectory($object['Key'])]),
                        'delete' => $this->router->generate('media.file_delete', ['fileName' => $objectPathInfo['basename'], 'folder' => $this->getDirectory($object['Key'])])
                    ],
                    'ext' => $objectPathInfo['extension'],
                    'size' => $object['Size'],
                    'lastModified' => $object['LastModified'],
                    'basename' => $objectPathInfo['filename']
                ];
            }
        }

        return $files;
    }

    /**
     * Get file.
     *
     * @param string $fileName
     * @param bool $info
     *
     * @return array
     */
    public function getFile(string $fileName, bool $info = false): array
    {
        if($this->cache){
            $key = md5(__METHOD__ . "-$fileName-$info");
            $cacheItem = $this->cache->getItem($key);
            if ($cacheItem->isHit()){
                return $cacheItem->get();
            }
        }
        $file = null;
        if (is_string($fileName)){
            $fileName = $this->getFilePath($fileName);
        }else if($fileName instanceof File){
            $pathInfo = pathinfo($file->getRealPath());
            $fileName = $this->getPath($pathInfo['dirname']) . $pathInfo['basename'];
        }

        if(!$this->isFileExists($fileName)){
            $this->uploadNonExistingFile($fileName);
        }
        $file = $this->backend->headObject([
            'Bucket' => array_get($this->options, 'bucket', null),
            'Key' => $fileName
        ]);

        $fileInfo = pathinfo($fileName);
        $file = array_merge(
                $file->getIterator()->getArrayCopy(),
                [
                    'ext' => $fileInfo['extension'],
                    'filename' => $fileInfo['basename'],
                    'folder' => $this->getDirectory($fileName),
                    'size' => $file['ContentLength'],
                    'basename' => $fileInfo['filename'],
                    'storage' => 'S3'
                ]
            );
        $file['lastModified'] = $file['LastModified'] ?? $file['lastModified'];
        if(array_has($file, 'LastModified')){
            unset($file['LastModified']);
        }
        if (!$info) {
            if($this->cache){
                $cacheItem->set($file);
                $this->cache->save($cacheItem);
            }

            return $file;
        }

        $returnValue = [
            'filename' => $fileInfo['basename'],
            'folder' => $this->getDirectory($fileName),
            'route' => [
                'render' => $this->router->generate('app.render_file', ['fileName' => $fileInfo['basename'], 'folder' => $this->getDirectory($fileName)]),
                'delete' => $this->router->generate('media.file_delete', ['fileName' => $fileInfo['basename'], 'folder' => $this->getDirectory($fileName)]),
            ],
            'ext' => $fileInfo['extension'],
            'size' => $file['ContentLength'],
            'lastModified' => $file['LastModified'] ?? $file['lastModified'],
            'basename' => $fileInfo['filename'],
        ];

        if($this->cache){
            $cacheItem->set($returnValue);
            $this->cache->save($cacheItem);
        }

        return $returnValue;
    }

    public function renameFile(string $fileName, string $rename): array
    {
        try {
            $file = $this->getFilePath($fileName);
            $rename = pathinfo($rename);

            if ($this->isFileExists($file)){
                $pathInfo = pathinfo($file);
                $actualFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $rename['basename'] . ".${pathInfo['extension']}";

                $i = 1;
                while($this->isFileExists($actualFile)){
                    $actualFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $rename['basename'] . "(${i}).${pathInfo['extension']}";
                }
                $pathInfo = pathinfo($actualFile);

                $this->backend->copyObject([
                    'Bucket' => array_get($this->options, 'bucket', null),
                    'Key' => $actualFile,
                    'CopySource' => urlencode(array_get($this->options, 'bucket', null) . DIRECTORY_SEPARATOR . $file),
                    'MetadataDirective' => 'REPLACE'
                ]);
                $this->deleteFile($file);

                $file = $this->backend->headObject([
                    'Bucket' => array_get($this->options, 'bucket', null),
                    'Key' => $actualFile
                ]);

                $fileInfo = [
                    'filename' => $pathInfo['basename'],
                    'route' => [
                        'render' => $this->router->generate('app.render_file', ['fileName' => $pathInfo['basename'], 'folder' => $this->getDirectory($actualFile)]),
                        'delete' => $this->router->generate('media.file_delete', ['fileName' => $pathInfo['basename'], 'folder' => $this->getDirectory($actualFile)]),
                    ],
                    'ext' => $pathInfo['extension'],
                    'size' => $file['ContentLength'],
                    'lastModified' => $file['LastModified'],
                    'basename' => $pathInfo['filename'],
                ];
                $status = ['success' => true, 'file' => $fileInfo, 'code' => 200];
            }else{
                $status = ['success' => false, 'error' => 'File Not Found', 'code' => 404];
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
            $this->backend->deleteObject([
                'Bucket' => array_get($this->options, 'bucket', null),
                'Key' => $this->getFilePath($file, $base)
            ]);

            return array_merge($fileInfo, ['success' => true, 'code' => 200]);
        } catch (IOExceptionInterface $e) {
            return array_merge($fileInfo, ['success' => false, 'error' => $e->getMessage(), 'code' => $e->getCode()]);
        }
    }

    public function createFile(string $file, bool $base = false): array
    {
        try {
            $this->backend->putObject([
                'Bucket' => array_get($this->options, 'bucket', null),
                'Key' => $this->getFilePath($file, $base)
            ]);

            return ['success' => true];
        } catch (IOExceptionInterface $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function isFileExists(string $filename): bool
    {
        return $this->backend->doesObjectExist(array_get($this->options, 'bucket', null), $this->getFilePath($filename));
    }

    public function getFileUri(string $fileName, ?string $folder = null, bool $presigned = true): string
    {
        if($this->cache){
            $key = md5(__METHOD__ . "-$fileName-$folder-$presigned");
            $cacheItem = $this->cache->getItem($key);
            if ($cacheItem->isHit()){
                return $cacheItem->get();
            }
        }

        $filePath = parent::getFileUri($this->trimPrefixPath($fileName), $folder);
        $cmd = $this->backend->getCommand('GetObject', [
            'Bucket' => array_get($this->options, 'bucket', null),
            'ResponseCacheControl' => 'public, max-age=900',
            'Key' => $filePath
        ]);
        $uri = '';
        if($presigned){
            $url = $this->backend->createPresignedRequest($cmd, '+20 minute');
            $uri = $url->getUri();
        }else{
            $uri = $this->backend->getObjectUrl(array_get($this->options, 'bucket', null), $filePath);
        }
        if($this->cache){
            $cacheItem->set($uri);
            $cacheItem->expiresAfter(DateInterval::createFromDateString('15 minutes'));
            $this->cache->save($cacheItem);
        }

        return $uri;
    }

    public function getContents(string $fileName): string
    {
        if(!$this->isFileExists($fileName)){
            $this->uploadNonExistingFile($fileName);
        }

        return $this->backend->getObject([
            'Bucket' => array_get($this->options, 'bucket', null),
            'Key' => $fileName
        ])['Body'];
    }

    public function putContents(string $fileName, string $data, ?array $options = [])
    {
        return $this->backend->putObject(array_merge([
            'Bucket' => array_get($this->options, 'bucket', null),
            'Key' => $fileName,
            'Body' => $data,
        ], $options));
    }
}
