# Environment Setup & Installation Guide

This guide explains how to set up the environment and run the project
locally.

------------------------------------------------------------------------

# 1. Environment Setup

## Requirements

Before running the project, install the following tools:

-   **PHP 8+**\
    Download: https://www.php.net/downloads

-   **MySQL**\
    Download: https://dev.mysql.com/downloads/mysql/

-   **XAMPP (recommended for Windows)**\
    Includes Apache + PHP + MySQL\
    Download: https://www.apachefriends.org/download.html

-   **Git**\
    Download: https://git-scm.com/downloads

------------------------------------------------------------------------

# 2. Installation

Clone the repository:

``` bash
git clone https://github.com/DuckZiiii300305/asm-web.git
```

Go to the backend directory:

``` bash
cd asm-web/backend
```

------------------------------------------------------------------------

# 3. Database Setup

Create a database:

``` sql
CREATE DATABASE asm_web;
```

Import the database schema:

``` bash
mysql -u root -p asm_web < 001_create_assets.up.sql
```

If using **XAMPP**, you can also import the SQL file using
**phpMyAdmin**.

------------------------------------------------------------------------

# 4. Configure Database Connection

Open the file:

    internal/database/database.php

Edit the database credentials if needed:

``` php
$host = "localhost";
$dbname = "asm_web";
$user = "root";
$password = "";
```

------------------------------------------------------------------------

# 5. Run the Project

If you are using **XAMPP**:

Move the project folder into:

    C:\xampp\htdocs\

Start the following services in **XAMPP Control Panel**:

-   Apache
-   MySQL

------------------------------------------------------------------------

# 6. Access the API

Open your browser and go to:

    http://localhost/asm-web/backend/public

Example endpoint:

    http://localhost/asm-web/backend/public/assets

------------------------------------------------------------------------

# 7. Example API Request

Using **curl**:

``` bash
curl http://localhost/asm-web/backend/public/assets
```

Search example:

``` bash
curl "http://localhost/asm-web/backend/public/assets/search?q=example"
```

------------------------------------------------------------------------

# 8. Project Repository

GitHub Repository:

https://github.com/DuckZiiii300305/asm-web
