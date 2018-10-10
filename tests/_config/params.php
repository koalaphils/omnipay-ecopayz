<?php
return [
    'DATABASE_HOST' => 'database', //container name, alias or service name
    'DATABASE_FOR_TESTS_NAME' => getenv('DATABASE_NAME') . '_tests',
    'DATABASE_USER' => getenv('DATABASE_USER'),
    'DATABASE_PASSWORD' => getenv('DATABASE_PASSWORD'),
];