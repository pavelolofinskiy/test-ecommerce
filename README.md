# Проект Каталог Товаров на PHP

Это простой проект каталога товаров с импортом и фильтрацией, реализованный на чистом PHP с использованием MySQL и Redis.

---

## Требования

- PHP = 8.1 с поддержкой PDO и Redis
- MySQL (или MariaDB)
- Redis сервер

---

## Установка и запуск

1. **Клонируйте репозиторий:**

   ```bash
   git clone https://github.com/pavelolofinskiy/test-ecommerce.git
   cd test-ecommerce

2. **Запустите миграции для создания и обновления структуры базы данных:**

   ```bash
   php migrate.php

3. **Импортируйте товары из XML:**
   
   ```bash
   php import.php

4. **Запустите встроенный PHP сервер:**

   ```bash
   php -S localhost:8000 -t public

5. **Откройте в браузере:**

   ```bash
   http://localhost:8000/index.html