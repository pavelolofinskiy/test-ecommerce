# Проєкт Каталог Товарів на PHP

Це простий проєкт каталогу товарів з імпортом та фільтрацією, реалізований на чистому PHP з використанням MySQL та Redis.

---

## Вимоги

- PHP >= 8.1 з підтримкою PDO та Redis
- MySQL (або MariaDB)
- Redis сервер

---

## Встановлення та запуск

1. **Клонувати репозиторій:**

   ```bash
   git clone https://github.com/pavelolofinskiy/test-ecommerce.git
   cd test-ecommerce

2. **Запустити міграції для створення та оновлення структури бази даних:**

   ```bash
   php migrate.php

3. **Імпортувати товари з XML:**
   
   ```bash
   php import.php

4. **Запустити вбудований PHP сервер:**

   ```bash
   php -S localhost:8000 -t public

5. **Відкрити у браузері:**

   ```bash
   http://localhost:8000/index.html