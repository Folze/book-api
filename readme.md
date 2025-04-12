📚 Book REST API with JWT Authentication

REST API для управления библиотекой книг. Реализовано на PHP с использованием MySQL и архитектуры MVC.

# Проект включает в себя

Регистрацию и авторизацию пользователя с использованием токенов JWT.

CRUD-запросы для книг {
GET — получение списка книг

POST — добавление книги

PUT — обновление данных книги

DELETE - удаление книги (с возможностью восстановления)
};

Возможность просматривать библиотеку других пользователей

Поиск книг через внешний API

# Тестировал все через Postman

<!--  -->

Примечание:
Все запросы, требующие авторизации, должны содержать в заголовке Authorization: Bearer token ваш токен.

1. Регистрация !.Весь json писать в body.!

POST http://localhost/register.php

{
"email": "ваш email",
"password": "ваш пароль"
}

<!--  -->

<!--  -->

2. Авторизация
   POST http://localhost/Controllers/AuthController.php

{
"email": "ваш email",
"password": "ваш пароль"
}

<!--  -->

3. Полученный "token" введите в Authorization

<!--  -->

4. Создание книги
   POST http://localhost/Controllers/BookController.php
   '{
   "title": "Новая книга",
   "author": "Автор",
   "genre": "Жанр",
   "year": 2025,
   "description": "Описание книги"
   }'

<!--  -->

5. Получение книги через id
   GET http://localhost/Controllers/BookController.php?id=1

<!--  -->

6. Получение всех книг
   http://localhost/Controllers/BookController.php

<!--  -->

7. Обновление данных о книге
   PUT http://localhost/Controllers/BookController.php?id=1
   {
   "title": "Обновленная книга",
   "author": "Новый автор"
   }

<!--  -->

8. Удаление книги
   DELETE http://localhost/Controllers/BookController.php?id=1

<!--  -->

9. Восстановление книги
PATCH "http://localhost/Controllers/BookController.php?restore=true&id=1"
<!--  -->

10. Google Books API  
    Используйте GET-запрос для поиска книг через Google Books API. Пример запроса:  
     GET http://localhost/Controllers/BookController.php?search=harry+potter

Этот запрос вернет список книг, найденных по запросу "harry potter".
