<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Context\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Exceptions\FormValidationException;
use DbBundle\Entity\User;

class UserController extends AbstractController
{    
    /**
     * @ApiDoc(
     *  description="Get user details. Called via AMS Client",
     *  statusCodes={
     *      200="Returned successful",
     *      401="Returned when the user is not authorized",
     *      404="Returned if user not exists"
     *  }
     * )
     */
    public function detailsAction(Request $request, string $username)
    {
        $repository = $this->getDoctrine()->getManager()->getRepository(User::class);
        $user = $repository->findOneBy(['username' => $username]);

        return $this->view($user);
    }
}
