# Project Submission - Silvio Porcellana

## Setup

## Quick Start

----

## Project Structure

### Database

#### Migrations

The system uses the Phinx migrations system (see <https://phinx.org/>) to manage the database structure and tables. This system uses the phynx.yml file (in the /src directory) to store database access data and other config options. For security reasons this script is not in version control, therefore if it's not present it needs to be created starting from the phinx.yml.dist file 

----
 
### Tests
The system uses [Codeception](https://codeception.com/) for creating and managing tests. All tests are located in the `/tests` directory and can be executed by executing `./src/vendor/codeception/codeception/codecept run` from the command line