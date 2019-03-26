<?php

declare(strict_types = 1);

namespace ApiBundle\Controller;

use ApiBundle\Request\RegistrationCodeRequest;
use ApiBundle\Request\TwoFactorCodeRequest;
use ApiBundle\RequestHandler\RegistrationCodeHandler;
use ApiBundle\RequestHandler\TwoFactorCodeHandler;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TwoFactorController extends AbstractController
{
    /**
     * @ApiDoc(
     *     description="Request 2fa code",
     *     section="2fa",
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
     *         },
     *         {
     *             "name"="purpose",
     *             "dataType"="string"
     *         }
     *     }
     * )
     */
    public function requestCodeAction(Request $request, TwoFactorCodeHandler $handler, ValidatorInterface $validator): View
    {
        $registrationCodeRequest = TwoFactorCodeRequest::createFromRequest($request);
        $violations = $validator->validate($registrationCodeRequest, null);
        if ($violations->count() > 0) {
            return $this->view($violations);
        }

        $code = $handler->handle($registrationCodeRequest);

        return $this->view($code);
    }
}