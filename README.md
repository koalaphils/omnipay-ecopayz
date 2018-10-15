# Piwi Back Office

**Piwi Back Office** is a customer management system.
```
update later
```

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.
See deployment for notes on how to deploy the project on a live system.

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

clone the repository using HTTPS
```
$ git clone https://your_username@bitbucket.org/zimitech/piwibackoffice.git
```
Create and import database
```
create database name: db_piwi (CHARACTER: utf8 COLLATE: utf8_general_ci)
database file: \tests\_data\piwi.sql
```

Run the installer
```
$ cd piwibackoffice
$ sh install.sh
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
update later
```

## Deployment
```
update later
```

## Running the Documentation

To run the documentation

```
update later
```

## License
```
update later.
```