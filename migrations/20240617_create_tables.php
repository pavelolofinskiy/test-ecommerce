<?php
// Убедись, что переменная $pdo передана из migrate.php

$pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS parameters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS parameter_values (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parameter_id INT NOT NULL,
        value VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE(parameter_id, value),
        FOREIGN KEY (parameter_id) REFERENCES parameters(id) ON DELETE CASCADE
    );
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS product_parameters (
        product_id INT NOT NULL,
        parameter_value_id INT NOT NULL,
        PRIMARY KEY (product_id, parameter_value_id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (parameter_value_id) REFERENCES parameter_values(id) ON DELETE CASCADE
    );
");