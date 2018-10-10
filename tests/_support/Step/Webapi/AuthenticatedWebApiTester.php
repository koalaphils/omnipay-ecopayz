<?php
namespace Step\Webapi;

class AuthenticatedWebApiTester extends \ApiTester
{

    # this is a non-expiring access token,
    # data of this access token are inserted via a dump file tests/_data/webapi_oauth_token.sql
    private $accessToken = 'OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw';
    public function loginUsingBearer()
    {
        $I = $this;
        $I->amBearerAuthenticated($this->accessToken);
    }

}