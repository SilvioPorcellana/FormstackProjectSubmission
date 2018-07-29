# Project Submission - Silvio Porcellana

This project is my submission for the requirements outlined in the "[Lead Engineering Project](Lead_Engineer_Project.pdf)" document.

### Quick Start

To immediately deploy and use the project, follow this procedure:
1) clone the repo and open a terminal window in the repo root
2) run `composer install`
3) make local copies of `/codeception.yml.dist`, `src/_config.ini.dist` and `src/phinx.yml.dist` (if you are using Vagrant all you have to do is change the S3 key in `_config.ini`)
4) (optionally) run `vagrant up` so you can start making API calls to `http://127.0.0.1:8080/api.php?request=v1/documents` (etc.) or to the
5) (optioally) create a vhost in Apache and make the document root point to `src/public`

## Project Structure

The main objective of the project is to provide a way to manipulate "documents", objects composed of different "rows" where each row is in the form `key => value` with additional metadata, from a RESTful interface. 

### Models

The two main models are `Document` and `DocumentRow`, with a one-to-many relationship between the former and the latter. These models implement the classical CRUD operations, somewhat replicating the usual "Records" models used in popular frameworks - a classic example is the ActiveRecord class in Yii2. This approach presents two benefits:
* total abstraction of controllers and other models from the underlying data storage (in this project the standard PHP `PDO` system is used but anything else could be used in the `save()`, `update()` etc. methods)
* skinny controllers: as all CRUD logic is abstracted away in models, controllers can be very slim thus making the code clearer to read and less error-prone

In addition to the two main models for Documents and their corresponding rows, the `\Models` folders contains two other classes:
* the `DocumentExport` class, used for providing "helper" methods when exporting `Documents`
* the `DocumentAPI` class. This is a pretty interesting widgets in itself: it extends the [RESTable](src/libs/RESTable.php) class which in turns allows any model to be accessed via REST. So this class exposes the actual methods that can be called via REST, allowing the API controller to just require a simple `processAPI` call to perform the required action (API for this project are outlined in the appropriate [RAML](documents.raml) file)  

### Controllers

As mentioned before, controllers are very skinny (given also the absence of views). In particular, the main controller (in `src/public`) is `api.php`, a very simple script that instantiates `DocumentAPI` and calls the `processAPI` method.

### Libs / Common

This folder contains two "helper" classes:
* `DocumentPDO`, the class that exposes two static methods to return the correct PDO (with the data taken from the project _config.ini)
* `RESTable`, the class that makes any other class (in this case, DocumentAPI) accessible via REST

----

## Setup

#### Config

The config details are kept in 3 files:
* `src/_config.ini` (please remember to edit the S3 key here)
* `src/phinx.ini` (for migrations)
* `src/codeception.yml` (for tests)

While the repo contains the disttribution version of these files, the included Vagrant box and scripts are already setup with the appropriate details so all is needed is just to copy the `.dist` files in files without the extension. In case Vagrant is not used, the files will have to be correctly filled with the data for the local db and API details

#### Migrations

The system uses the Phinx migrations system (see <https://phinx.org/>) to manage the database structure and tables. This system uses the phynx.yml file (in the /src directory) to store database access data and other config options. For security reasons this script is not in version control, therefore if it's not present it needs to be created starting from the phinx.yml.dist file. After creating the phinx.yml file the following command needs to be called in the `src` folder to execute the required migrations:
```vendor/bin/phinx migrate -e development```
 
### Tests
The system uses [Codeception](https://codeception.com/) for creating and managing tests. All tests are located in the `/tests` directory and can be executed by executing `./src/vendor/codeception/codeception/codecept run` from the command line

----

### Vagrant

Once the `vagrant up` command is executed from the root of the project, the system will be automatically setup, the database created, migrations executed and also the tests performed. This will ensure that the system is correctly setup so that it can be immediately accessed with calls to `http://127.0.0.1:8080/api.php?request=v1/...` such as `http://127.0.0.1:8080/api.php?request=v1/exportTo/2`
