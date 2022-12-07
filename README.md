# test_php_rabbitmq
Тестовое задание.

## Установка
Вся сборка происходит через docker-compose:
<pre>docker-compose up -d</pre>

## SQL запросы
Запрос на получение общего количества записей
<pre>SELECT count(id) FROM `response`;</pre>
Запрос на получение количества записей в которыйх в header которых встречается поле 'new' со значением 1 
<pre>SELECT * FROM `response` WHERE JSON_EXTRACT(`header`,'$.new') LIKE '% 1"%';</pre>
