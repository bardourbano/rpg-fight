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
It's a simple API, with a client, that represents a battle in tabletop rpgs like _Dungenons & Dragons_ and _Tormenta_.
### Built With
As I am a main Laravel developer at the moment, and had little free time, I've choosen to use Laravel to build this API and the command-line clint used to test it.
* [Laravel](https://laravel.com)



<!-- GETTING STARTED -->
## Getting Started

This projet is simple but there are some things you should do, and have, to run it.
### Prerequisites

#### MySQL
  
This projet was tested with SQLite and MySQL 8.0. \
I recomend to use MySQL as SQLite was only used for the tests with phpunit.

> It should be compatible with MariaDB, but it was not tested.

#### PHP 8
This project was built usind PHP 8 and you shoul use it too.

> Laravel does support older versions, but match expressions are used in the code and versions prior 8.x don't have it.

#### Composer
It uses Composer as package mannager and you will need it to install the projetc.

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
5. Create a database for the app
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
   > If you have problems with this command, you can do it manually by using this command:
   > ```sh
   > php artisan migrate:fresh --seed
   > ```
8. Run the API
   If the development serve did not start with the previous command, you can start it by yourself using this:
   ```sh
   php artisan serve
   ```
   > By default it starts at http:127.0.0.1:8000, but you can modify it by passing the paramenters `--host` and `--port` to the commands `game:setup` or `serve`.
9. Run the client
    Open another terminal in the poject's folder and start the client
    ```sh
    php artisan game:start
    ```

<!-- ROADMAP -->
## Roadmap

A dedicated client is planned, but not in near future as well an rich API documentation that will be concluded soon.
