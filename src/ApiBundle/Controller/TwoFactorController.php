<?php

declare(strict_types = 1);

namespace ApiBundle\Controller;

use ApiBundle\Request\RegistrationCodeRequest;
use ApiBundle\RequestHandler\RegistrationCodeHandler;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TwoFactorController extends AbstractController
{
    /**
     * @ApiDoc(
     *     description="Register Member",
     *     views = {"piwi"},
     *     requirements={
     *         {
     *             "name"="email",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="phone_number",
     *             "dataType"="string"
     *         },
     *         {
     *             "name"="country_phone_code",
     *             "dataType"="string"
     *         }
     *     }
     * )
     */
    public function requestRegistrationCodeAction(Request $request, RegistrationCodeHandler $handler, ValidatorInterface $validator): View
    {
        $registrationCodeRequest = RegistrationCodeRequest::createFromRequest($request);
        $violations = $validator->validate($registrationCodeRequest, null);
        if ($violations->count() > 0) {
            return $this->view($violations);
        }

        $code = $handler->handle($registrationCodeRequest);

        return $this->view($code);
    }
}