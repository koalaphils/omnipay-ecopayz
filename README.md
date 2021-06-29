# Piwi Backoffice

**Piwi Backoffice**  is a customer management system.
It was use by customer service of ZimiTech to manager their customer and track the transaction made by customer.

The system was build using **Symfony 3.3**

## Getting Started

These instruction will let you setup the project to your local environment.

### Technology used

- PHP >= 7.2
- MySQL >= 5.8
- Nginx
- Redis

### Steps to run it

Currently the system is ready use for docker container.
> **Note:** This step is for development environment

```bash
$ mkdir PIWI
$ cd PIWI
$ git clone git@bitbucket.org:zimitech/piwibackoffice.git
$ vim docker-compose.yml
```

docker-compose.yml
```yaml
version: "3.4"
services:
  database:
    image: mysql:8.0.15
    command: --default-authentication-plugin=mysql_native_password
    volumes:
      - database:/var/lib/mysql
    environment:
      - MYSQL_ROOT_PASSWORD=backofficepw
      - MYSQL_PASSWORD=backofficepw
      - MYSQL_DATABASE=backoffice2
      - MYSQL_USER=backoffice
  mailhog:
    image: mailhog/mailhog
    command:
      - "-smtp-bind-addr"
      - "0.0.0.0:25"
    user: root
    ports:
      - 8025:8025
  adminer:
    image: adminer
    ports:
      - 9080:8080

  piwibo-php:
    build:
      context: ./piwibackoffice/
      target: prod
    volumes:
      - ./piwibackoffice/:/var/www/html
      - uploads:/uploads
    depends_on:
      - database
      - mailhog
      - adminer
    environment:
      - SYMFONY_ENV=dev
      - APP_SECRET=<value-here>
      - CUSTOMER_TEMP_PASSWORD=super@c66p@$$w0rd
      - JWT_KEY=YourSampleKeyHere
      - SLACK_TOKEN=<value-here>
      - SLACK_CHANNEL=<value-here>
      - MAIL_TRANSPORT=smtp
      - MAIL_HOST=mailhog
      - MAIL_USER=~
      - MAIL_PORT=25
      - MAIL_PASSWORD=~
      - MAIL_FROM=support@piwi247.com
      - MAIL_ENCRYPTION=~
      - PIN_API_URL=http://proxy.pinny88.com/b2b
      - PIN_API_AGENT_KEY=<value-here>
      - PIN_API_SECRET_KEY=<value-here>
      - PIN_AGENT_CODE=<value-here>
      - TWILIO_SID=<value-here>
      - TWILIO_TOKEN=<value-here>
      - TWILIO_FROM=<value-here>
      - WS_URL=ws://localhost:83/ws/
      - WS_WAMP_URL=http://websocket:9092
      - DATABASE_DRIVER=pdo_mysql
      - DATABASE_HOST=database
      - DATABASE_NAME=backoffice2
      - DATABASE_USER=backoffice
      - DATABASE_PASSWORD=backofficepw
      - ECOPAYZ_TEST_MODE=true
      - BC_KEY=<value-here>
      - BC_WALLET_URL=http://wallet:3000
      - BC_XPUB__HOST=xpubscanner
      - BC_XPUB_PORT=22
      - BC_XPUB__USER=xpub
      - BC_XPUB__PASSWORD=
      - BC_CALLBACK_HOST=https://cydrick.serveo.net
      - TRUSTED_PROXIES=172.0.0.0/8,10.0.0.0/8,192.0.0.0/8
      - APP_TIMEZONE=Etc/GMT+4
      - REDIS_HOST=redis
  piwibo-scheduler:
    build:
      context: ./piwibackoffice/
      target: prod
    entrypoint: ""
    restart: unless-stopped
    command:
      - php
      - app/console
      - jms-job-queue:schedule
      - --env=dev
    volumes:
      - ./piwibackoffice/:/var/www/html
    depends_on:
      - database
      - mailhog
      - adminer
  piwibo-jobs:
    build:
      context: ./piwibackoffice/
      target: prod
    restart: unless-stopped
    entrypoint: ""
    command:
      - php
      - app/console
      - jms-job-queue:run
      - --env=dev
    volumes:
      - ./piwibackoffice/:/var/www/html
    depends_on:
      - database
      - mailhog
      - adminer
  piwibo-web:
    build:
      context: ./piwibackoffice/
      target: webservice
    depends_on:
      - piwibo-php
    ports:
      - 81:80
    volumes:
      - ./piwibackoffice/:/var/www/html
    environment:
      - PHPHOST=piwibo-php
      - PHPPORT=9000
  websocket:
    image: registry.zmtsys.com/websocket
  
  websocket-proxy:
    image: registry.zmtsys.com/websocket-proxy
    ports:
    - '83:80'
    depends_on:
      - websocket
  
  wallet:
    image: registry.zmtsys.com/blockchain-wallet
    environment:
    - BLOCKCHAIN_SSH_USER=xpub
    - BLOCKCHAIN_SSH_PASS=zmtsysxpub

  redis:
    image: redis

volumes:
  database:
  uploads:
```

> Rember to replace all `<value-here>` this value are mostly third party inforamtion and sensetive.

After saving the file

```bash
$ docker-compose up -d
```

Wait for it, once everything was done, you can access backoffice thru http://localhost:81

## Deployment

For deploying, we use image instead of build in docker-compose.yml.<br/>
Bur remember the `piwibo-scheduler` and `piwibo-jobs` must have same environment to `piwibo-php`.<br/>
To share environment in this three container you can use `env_files` instead of `environments`.<br/>
Note, if you change the content of declared env_files and you do `docker-compose up -d`,
this will not recreate the container, means the environment inside container will not be updated,
so when you update the content you need to recreate the three containers `docker-compose up -d --force-recreate`.

### Images

**piwibo-web** : Backoffice nginx<br/>
**piwibo-php** : Backoffice php-fpm

For `piwibo-scheduler` and `piwibo-jobs`, this should use `piwibo-php` image.
