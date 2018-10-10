<?php

namespace MediaBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        return $this->render('MediaBundle:Default:index.html.twig');
    }

    public function listAction(Request $request)
    {
        $filters = $request->request->all();
        $files = $this->getManager()->getFiles($filters);

        return new JsonResponse($files);
    }

    public function uploadAction(Request $request)
    {
        $this->getSession()->save();
        $status = $this->getManager()->uploadFile($request->files->get('file'), $request->get('folder', ''));
        if ($status['success']) {
            $message = 'Upload Success: ' . $status['filename'];
        } else {
            $message = $status['error'];
        }

        return new JsonResponse($message, $status['code']);
    }

    public function renameAction(Request $request)
    {
        $this->getSession()->save();
        $status = $this->getManager()->renameFile($request->get('filename'), $request->get('rename'));
        if ($status['success']) {
            $message = [
                'title' => 'Renamed',
                'message' => 'Successfully Renamed',
                'id' => $request->get('id'),
                'file' => $status['file'],
            ];
        } else {
            $message = [
                'title' => 'Error',
                'message' => $status['error'],
                'id' => $request->get('id'),
            ];
        }

        return new JsonResponse($message, $status['code']);
    }

    public function deleteAction(Request $request, $fileName)
    {
        $status = $this->getManager()->deleteFile($fileName);
        $notifications = [];

        if ($status['success']) {
            $notifications[] = [
                'type' => 'success',
                'title' => $this->getTranslator()->trans('notification.deleted.success.title', [], 'MediaBundle'),
                'message' => $this->getTranslator()->trans(
                    'notification.deleted.success.message',
                    ['%file%' => $fileName],
                    'MediaBundle'
                ),
            ];
        } else {
            $notifications[] = [
                'type' => 'error',
                'title' => $this->getTranslator()->trans('notification.deleted.error.title', [], 'MediaBundle'),
                'message' => $status['error'],
            ];
        }

        return new JsonResponse(['id' => $request->get('id'), '__notifications' => $notifications], $status['code']);
    }

    /**
     * Get media manager.
     *
     * @return \MediaBundle\Manager\MediaManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('media.manager');
    }
}
