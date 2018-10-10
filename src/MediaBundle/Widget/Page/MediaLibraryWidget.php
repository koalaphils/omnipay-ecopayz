<?php

namespace MediaBundle\Widget\Page;

use AppBundle\Widget\AbstractPageWidget;
use MediaBundle\Manager\MediaManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Description of MediaLibraryWidget
 *
 * @author cydrick
 */
class MediaLibraryWidget extends AbstractPageWidget
{
    public static function defineDetails(): array
    {
        return [
            'title' => 'Media Library'
        ];
    }

    public function onGetList(array $data): array
    {
        $files = $this->getMediaManager()->getFilesInFolder($this->getRootPath());
        foreach ($files as &$file) {
            $file['title'] = $file['basename'];
        }

        return $files;
    }

    public function onRenameFile(array $data): JsonResponse
    {
        $filename = rtrim($this->getRootPath()) . '/' . $data['filename'];
        $renameTo = rtrim($this->getRootPath()) . '/' .$data['renameTo'];

        $result = $this->getMediaManager()->renameFile($filename, $renameTo);
        if ($result['success']) {
            return new JsonResponse(['file' => $result['file']]);
        } else {
            return new JsonResponse(['error' => $result['error']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function onUploadFile(array $data): JsonResponse
    {
        $result = $this->uploadFile();

        if ($result['success']) {
            return new JsonResponse(['filename' => $result['filename'], 'folder' => $result['folder']]);
        } else {
            return new JsonResponse(['error' => $result['error']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function onDeleteFile(array $data): JsonResponse
    {
        $result = $this->deleteFile($data['filename']);

        if ($result['success']) {
            return new JsonResponse([]);
        } else {
            return new JsonResponse(['error' => $result['error']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function deleteFile(string $file): array
    {
         $filename = rtrim($this->getRootPath()) . '/' . $file;

         return $this->getMediaManager()->deleteFile($filename);
    }

    public function uploadFile(): array
    {
        $file = $this->getCurrentRequest()->files->get('file');

        return $this->getMediaManager()->uploadFile($file, $this->getRootPath());
    }

    protected function getRootPath(): string
    {
        return $this->property('rootPath', '');
    }

    protected function getBlockName(): string
    {
        return 'medialibrary';
    }

    protected function getView(): string
    {
        return 'MediaBundle:Widget:Page/medialibrary.html.twig';
    }

    protected function getMediaManager(): MediaManager
    {
        return $this->container->get('media.manager');
    }
}
