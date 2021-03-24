<?php

namespace ApiBundle\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class BannerImageController extends AbstractController
{
    /**
     * @ApiDoc(
     *  description="Gets list of banner images"
     * )
     */
    public function listAction(): \FOS\RestBundle\View\View
    {
        $bannerImages = $this->getBannerImageManager()->list();

        return $this->view($bannerImages);
    }

    /**
     * @ApiDoc(
     *  description="View banner image",
     *  requirements={
     *      {
     *          "name"="filename",
     *          "dataType"="string",
     *          "description"="filename of the banner image"
     *      }
     *  }
     * )
     */
    public function viewAction(string $filename)
    {
        try {
            $relativePath = $this->getBannerImageManager()->view($filename);

            return new BinaryFileResponse($relativePath);
        } catch (FileNotFoundException $e) {
            return $this->view($e->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }

    private function getBannerImageManager(): \ApiBundle\Manager\BannerImageManager
    {
        return $this->get('api.banner_image.manager');
    }
}