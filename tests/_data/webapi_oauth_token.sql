-- this is the non-expiring access token that we will use for testing the API
-- this shouold be copy/pasted to AuthenticatedWebApiTester::$accessToken
delete from oauth2_refresh_token where client_id = 7;
delete from oauth2_access_token where client_id = 7;
delete from oauth2_client where id = 7;
INSERT  INTO `oauth2_client` VALUES (7,'1wfiecxlsldwgcwkwgkw4s048s044scg4ooo04wsos840ocgw8','a:0:{}','pu54qe852hccoswssk0g8ws04wso4c4484cwcks8cogc000c8','a:1:{i:0;s:8:\"password\";}');
INSERT  INTO `oauth2_access_token` VALUES (127946,7,1,'OTAyY2VmOTdkNGZmOTcxOTM3ZDY5ZjE5ZmMyMzliYzQwOWYzZDBhYjFkMTBlYTNiNjU5YTdlNmU2ODhiMzI1Mw',0,'api','127.0.0.1');
INSERT  INTO `oauth2_refresh_token` VALUES (127945,7,1,'MDBhNjdlNTIyNTY0ZDUyYTkyY2IwYWJjNmUyMzFmMTg3M2VkMDNhODFiZDZiZmJhODEzNzM3MTBjZGIwODJhMQ',1530587501,'api');


