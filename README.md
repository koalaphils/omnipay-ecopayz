# AC66 Back Office

**AC66 Back Office** is a customer management system.
It was use by Customer Support of ZimiTech to manage their customer.
This system targeted Europe customers.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

Before starting you need to install some services for your development.

**PHP**
Version 7.1
Check the link on how to install PHP.
[http://php.net/manual/en/install.php](http://php.net/manual/en/install.php)

**MySQL**
Version >= 5.7
Check the link on how to install MySQL.
[https://dev.mysql.com/doc/refman/5.7/en/installing.html](https://dev.mysql.com/doc/refman/5.7/en/installing.html)

**Git**
Check the link on how to install Git
[https://www.atlassian.com/git/tutorials/install-git](https://www.atlassian.com/git/tutorials/install-git)

**Composer**
Check the link on how to install composer
[https://getcomposer.org/doc](https://getcomposer.org/doc)

### Installing

Before installing the project make sure you have install all **Prerequisites**

Clone the repository using SSH
```
$ git clone git@bitbucket.org:zimitech/ac66bo.git
```

Or you can clone the repository using HTTPS
```
$ git clone https://your_username@bitbucket.org/zimitech/ac66bo.git
```

Run the installer
```
$ cd ac66bo
$ sh install.sh
```

Migrate Database

```
$ php app/console doctrine:migrations:migrate
```

> **Note:** Make sure you have created the database before running the above command.
> If database is not yet exists. You can create a database manually in your mysql client or you can run the below command. ``` $ php app/console doctrine:database:create ```

Create Upload Folder
```
$ mkdir your_storage_directory/upload
```

> **Note:** The *upload_folder* in your parameter must have your real path of the directory in your server and this folder must be writable.

Run the app setup

> We only recommend you to run this only once.

```
$ php app/console app:setup
```

Run the System

```
$ php app/console server:run
```

You are now done installing and running the system.
Now open your browser and access `http://localhost:8000`

## Updating to new build

As a note for updating from new build.
After you updated your old build to new build.
You must run install.sh.

Running install.sh
```
$ sh install.sh
```

## Installing using Docker

The instruction below are for workstation

```
$ git clone git@bitbucket.org:zimitech/ac66bo.git && cd ac66bo
$ # OR
$ git clone https://your_username@bitbucket.org/zimitech/ac66bo.git && cd ac66bo

$ cd .docker
$ cp .env-dist .env
$ vim .env
# Change the necessary details like the database password.

# Change to following

from: COMPOSE_FILE=docker-compose.yml;docker-compose-test.yml
to: COMPOSE_FILE=docker-compose.yml;docker-compose-dev.yml

from: ${BASE_IMAGE}
# the below is just example
to: backoffice_base

from: ${BASE_IMAGE_TAG}
# the below is just example
to: latest

# Now save and exit

$ docker build -t backoffice_base:latest -f DockerfileBase .

$ docker-compose build --no-cache && docker-compose up -d
# OR
$ docker-compose up -d --build
```

Some commands you need to check.

```
# Command if there are changes in .docker/DockerfileBase
$ docker build -t backoffice_base:latest -f DockerfileBase .
$ docker-compose build --no-cache

# Command to start the containers
$ docker-compose start

# Command to stop the containers
$ docker-compose stop

# Command to update and start a container
$ docker-compose up -d

# Command to update, start a container and build the image
# Only do this if there are changes in DockerfileBase or Dockerfile
$ docker-compose up -d --build

```


## Deployment

For deployment of the system in **Production** environment just follow this link [http://119.9.74.57/Main/AC66Deployment](http://119.9.74.57/Main/AC66Deployment).

Run the Cron Job (every minute) for auto decline transaction
```
cmd>crontab -e
* * * * * /usr/bin/php /usr/share/nginx/html/deployer/prod/cms/current/app/console transaction:decline system -vvv --env=prod >> /usr/share/nginx/html/deployer/prod/cms/current/var/logs/autoDecline-`date +\%Y\%m\%d`-cron.log 2>&1
```

Add to supervisor configuration for commission computation and payout
[program:jms_job_queue_runner]
command=/usr/bin/php {{change-this-to-root-dir}}/app/console jms-job-queue:run --env=prod --verbose
process_name=%(program_name)s
numprocs=5
autostart=true
autorestart=true
startsecs=5
startretries=10
user={{change-this-to-username}}
stdout_logfile={{change-this-to-root-dir}}/var/logs/prod.jms_job_queue_runner.out.log
stderr_logfile={{change-this-to-root-dir}}/var/logs/prod.jms_job_queue_runner.error.log

[program:jms_job_queue_schedule]
command=/usr/bin/php {{change-this-to-root-dir}}/app/console jms-job-queue:schedule --env=prod --verbose
process_name=%(program_name)s
numprocs=5
autostart=true
autorestart=true
startsecs=5
startretries=10
user={{change-this-to-username}}
stdout_logfile={{change-this-to-root-dir}}/var/logs/prod.jms_job_queue_schedule.out.log
stderr_logfile={{change-this-to-root-dir}}/var/logs/prod.jms_job_queue_schedule.error.log

## Running the Documentation

To run the documentation

```
$ cd docs
$ composer install
$ vendor/bin/sculpin generate --env=prod
```
The generate file will be in `output_prod`.
Now you can point it the web server to output_prod.