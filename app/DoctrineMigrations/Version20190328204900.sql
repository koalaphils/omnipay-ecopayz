SET time_zone = '+00:00';

INSERT INTO `oauth2_client` (`id`, `random_id`, `redirect_uris`, `secret`, `allowed_grant_types`) VALUES
(1,	'4bisobliwqgw0w08s0kw0swog8w4co4os0kk04cw8s08000scs',	'a:0:{}',	'2wfdnjv550isosc0ksscwg8o0gcgg8kkcg40go8g044so0sk0o',	'a:2:{i:0;s:8:\"password\";i:1;s:13:\"refresh_token\";}');

INSERT INTO `oauth2_access_token` (`id`, `client_id`, `user_id`, `token`, `expires_at`, `scope`, `ip_address`) VALUES
(1,	1,	1,	'MDgzNjVhN2E0MjY2NjcyZTE0M2E1NzIwMGI3OGFmN2RjNmVhYmQ4OTE3M2I4NjEyNGE0MDhjMTM4OTgwNmUzMg',	NULL,	'api',	'*');

INSERT INTO `oauth2_refresh_token` (`id`, `client_id`, `user_id`, `token`, `expires_at`, `scope`) VALUES
(1,	1,	1,	'YTg0MmRlODdmNzc0ZmVmOWZlYmRkMTg5YWU3NWE3ZGRiYzRhNGYzYjcwMWI3YzFiODYwMGRmYmI5YWE1ZDY0ZA',	NULL,	'api');