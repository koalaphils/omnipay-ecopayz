<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MediaBundle\Manager;

use Aws\S3\S3Client;
use MediaBundle\Adapter\AbstractFileStorage;
use MediaBundle\Adapter\FileSystemAdapter;
use MediaBundle\Adapter\S3Adapter;
use AppBundle\Manager\AbstractManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Cache\Adapter\FilesystemAdapter as FileSystemCache;

/**
 * Description of MediaManager.
 *
 * @author Melvin D. Protacio<melvin.protacio@zmtsys.com>
 */
class MediaManager extends AbstractManager
{
    /**
     * @var AbstractFileStorage
     */
    protected $storageProvider;


    /**
     * @param S3Client|Filesystem $storageProvider
     */
    public function init($storageProvider)
    {
        if ($storageProvider instanceof S3Client) {
            $this->storageProvider = new S3Adapter($storageProvider, $this->getRouter(), 'uploads/',
                [
                    'path' => ltrim($this->getContainer()->getParameter('upload_folder'), DIRECTORY_SEPARATOR),
                    'bucket' => $this->getContainer()->getParameter('aws_s3.bucket'),
                    'cache' => new FileSystemCache('media.library')
                ]);
        } else if ($storageProvider instanceof Filesystem) {
            $this->storageProvider = new FileSystemAdapter($storageProvider, $this->getRouter(), '/uploads/',
                [
                    'path' => $this->getContainer()->getParameter('upload_folder'),
                ]);
        }
    }

    public function getFiles($filter = []): array
    {
        return $this->storageProvider->getFilesInFolder(null, $filter);
    }

    public function getFilesInFolder(string $folder, array $filters = []): array
    {
        return $this->storageProvider->getFilesInFolder($folder, $filters);
    }

    /**
     * Get file.
     *
     * @param type $fileName
     * @param type $info
     *
     * @return array
     */
    public function getFile(?string $fileName = null, ?bool $info = false)
    {
        if ($fileName === null) return [];

        try {
            return $this->storageProvider->getFile($fileName, $info);
        } catch(FileNotFoundException $fne) {
            $pathInfo = pathinfo($fileName);
            return [
                'error' => 'File Not Found',
                'filename' => $fileName,
                'folder' => $this->storageProvider->getDirectory($fileName),
                'basename' => $pathInfo['filename'],
                'ext' => $pathInfo['extension'],
                'size' => 0
            ];
        }
    }

    public function renameFile($fileName, $rename)
    {
        return $this->storageProvider->renameFile($fileName, $rename);
    }

    /**
     * @param UploadedFile $file
     * @param string       $folder
     * @param string       $filename
     *
     * @return array
     */

    public function uploadFile(UploadedFile $file, $folder = '', ?string $filename = '')
    {
        $pathInfo = pathinfo($this->storageProvider->getFilePath($this->getPath($folder) . ($filename ?? $file->getClientOriginalName())));
        try {
            $mimeType = $file->getMimeType();
            if (stripos($mimeType, 'image') === false){
                $fileInfo = $this->storageProvider->uploadRawFile($file, $folder, $filename);
            }else{
                $fileInfo = $this->storageProvider->compressUploadFile($file, $folder, $filename);
            }

            if(array_has($fileInfo, 'error')){
                $status = ['success' => false, 'error' => $fileInfo['error'], 'folder' => $fileInfo['folder'], 'code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'filename' => $filename ?? $pathInfo['basename']];
            }else{
                $status = ['success' => true, 'filename' => $fileInfo['filename'], 'folder' => $fileInfo['folder'], 'code' => Response::HTTP_OK];
            }

        } catch (\Exception $e) {
            $status = ['success' => false, 'message' => 'File too large.', 'code' => Response::HTTP_UNPROCESSABLE_ENTITY, 'filename' => $pathInfo['basename']];
        }

        return $status;
    }

    public function compressUploadFile(UploadedFile $file, string $folder, ?string $filename = null): array
    {
        return $this->storageProvider->compressUploadFile($file, $folder, $filename);
    }

    public function deleteFile($file, $base = false): array
    {
        return $this->storageProvider->deleteFile($file, $base);
    }

    public function createFile($file, $base = false): array
    {
        return $this->storageProvider->createFile($file, $base);
    }

    public function getFilePath($file, $base = false): string
    {
        return $this->storageProvider->getFilePath($file, $base);
    }

    public function getPath($folder = ''): string
    {
        return $this->storageProvider->getPath($folder);
    }

    public function isFileExists(string $filename): bool
    {
        return $this->storageProvider->isFileExists($filename);
    }
    public function getFileUri($filename, ?string $folder = null, bool $presigned = true){
        return $this->storageProvider->getFileUri($filename, $folder, $presigned);
    }

    public function getCustomerDocumentRoot()
    {
        return $this->getContainer()->getParameter('customer_folder') ?? 'customerDocuments';
    }

    public function renderFile(string $filename, ?string $folder = ''): StreamedResponse
    {
        $fileUri = $this->storageProvider->getFileUri($filename, $folder);

        return new StreamedResponse(function () use ($fileUri) {
            readfile($fileUri);
        }, 200, [
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "inline; filename=\"${filename}\"",
            'Cache-Control' => 'public, max-age=900'
        ]);
    }

    public function checkIfFileInUploadFolder($uploadFolder, $relativePath): bool
    {
        return $this->storageProvider->checkIfFileInUploadFolder($uploadFolder, $relativePath);
    }

    public function putContents($fileName, $data)
    {
        return $this->storageProvider->putContents($fileName, $data);
    }
    public function getContents($filename)
    {
        return $this->storageProvider->getContents($filename);
    }

    protected function getRepository()
    {
    }
}
