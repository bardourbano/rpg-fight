<!--
*** Thanks for checking out the Best-README-Template. If you have a suggestion
*** that would make this better, please fork the repo and create a pull request
*** or simply open an issue with the tag "enhancement".
*** Thanks again! Now go create something AMAZING! :D
-->



<!-- PROJECT SHIELDS -->
<!--
*** I'm using markdown "reference style" links for readability.
*** Reference links are enclosed in brackets [ ] instead of parentheses ( ).
*** See the bottom of this document for the declaration of the reference variables
*** for contributors-url, forks-url, etc. This is an optional, concise syntax you may use.
*** https://www.markdownguide.org/basic-syntax/#reference-style-links
-->
<!--
[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![Issues][issues-shield]][issues-url]
[![MIT License][license-shield]][license-url]
[![LinkedIn][linkedin-shield]][linkedin-url]
-->


<!-- PROJECT LOGO -->
<br />
<p align="center">
  <h3 align="center">RPG Fight</h3>

  <p align="center">
    A simple API.
  </p>
</p>

<!-- ABOUT THE PROJECT -->
## About The Project
This project was parte of the Juniur Software Engineer screening for the Imusica division of Claro Brasil.
It's a simple representation of a battle in tabletop rpgs like _Dungenons & Dragons_ and _Tormenta_.

### Built With
I was built with PHP, using Laravel for the web API, composer for package management,SQLite and PHPunit for tests and MySQL as database.
* [Laravel](https://laravel.com)
* [Composer](https://getcomposer.org)
* [PHP](https://www.php.net)
* [SQLite](https://www.sqlite.org)
* [MySQL](https://www.mysql.com)
* [PHPUnit](https://phpunit.de/index.html) 



<!-- GETTING STARTED -->
## Getting Started
### Prerequisites

#### MySQL
  
This projet was tested with SQLite and MySQL 8.0.

#### PHP 8
This project was built usind PHP 8.

> Laravel does support older versions, there are control structures used in this project that are not present in versions prior to 8.x.

#### Composer
Please, user the Composer package manager to install the project.

### Installation and Usage

1. Clone the repo 
    ```sh
    git clone https://github.com/bardourbano/rpg-fight.git
    ```
2. Install with composer
   ```sh
   cd ./rpg-fight
   composer install
   ```
3. Copy the `.env` file
   ```sh
   cp .env.example .env
   ```
4. Generate your app key
   ```sh
   php artisan key:generate
   ```
5. Create a database for the app. (This command shoul be run through an MySQL CLI or IDE)
    ```sql
    CREATE DATABASE rpg_fight
    ```
6. Update the `.env` file with the needed information
    ```php
    APP_URL=http://localhost:8000
    DB_CONNECTION=mysql
    DB_HOST=localhost
    DB_PORT=3306
    DB_DATABASE=rpg_fight
    DB_USERNAME=rpg_fight
    DB_PASSWORD=laravel
    ```
    These are examples included in the `.env` file. You can use then and/or modify to the especific configs from you ambient.
7. Run the setup
   ```sh
   php artisan game:setup
   ```
   > If you have problems with this command, try this instead:
   > ```sh
   > php artisan migrate:fresh --seed
   > ```
8. Run the API
   If the development server fails to start with the previous command, you can start it using this command:
   ```sh
   php artisan serve
   ```
   > By default it starts at http://127.0.0.1:8000, but you can modify it with the paramenters `--host` and `--port`.
9. Run the client
    Open the poject's folder another terminal and start the client:
    ```sh
    php artisan game:start
    ```
