# CPM v3

WordPress-плагин управления проектами (аналог Basecamp): задачи, проекты клиентов, команда, права на уровне проекта.

| | |
| --- | --- |
| **Код плагина** | [`source/`](source/) |
| **Спецификации** | [`docs/`](docs/README.md) |
| **Тесты** | [`tests/`](tests/README.md) |
| **Агенты / конвенции** | [`AGENTS.md`](AGENTS.md) |

Рабочая ветка: `v3`.

---

## Требования

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Compose V2)
- Git
- Свободные порты: `8080`, `8081`, `3306`, `1025`, `8025`

---

## Быстрый старт (стенд)

1. Клонируйте репозиторий и перейдите в каталог проекта.
2. Создайте `.env` из примера и задайте пароли MySQL:

   ```bash
   cp .env.example .env
   ```

3. Поднимите стенд (первый запуск соберёт образы и скачает WordPress в Docker volume `wp_data`):

   ```bash
   docker compose up -d --build
   ```

4. Откройте мастер установки WordPress:

   **http://localhost:8081**

   Параметры БД подставляются из окружения через [`www/wp-config.php`](www/wp-config.php) — шаг настройки БД обычно пропускается. Создайте администратора сайта и активируйте плагин **CPM** в админке.

Остановка:

```bash
docker compose down
```

Данные сохраняются между перезапусками:
- MySQL — volume `db_data`
- файлы WordPress — volume `wp_data`

В репозитории в `www/` лежит только шаблон `wp-config.php`. При старте `wp-init` делает на него symlink внутри volume (правки на хосте подхватываются сразу).

---

## Сервисы и порты

| Сервис | URL / порт | Назначение |
| ------ | ---------- | ---------- |
| WordPress (PHP 8.4) | http://localhost:8081 | Основной стенд CPM v3 |
| WordPress (PHP 7.4) | http://localhost:8080 | Легаси / импорт данных |
| MySQL 8 | `localhost:3306` | БД (доступ с хоста) |
| MailHog UI | http://localhost:8025 | Просмотр исходящей почты |
| MailHog SMTP | `localhost:1025` | SMTP для тестов почты |

Оба PHP-контейнера монтируют один и тот же volume `wp_data` и одну БД. Канонический URL сайта — **8081** (`WP_HOME` / `WP_SITEURL`); порт 8080 нужен для проверки на PHP 7.4, не как второй независимый сайт.

Плагин подключается volume-ом: `source/` → `wp-content/plugins/cpm`.

---

## Конфигурация

### `.env`

Скопируйте [`.env.example`](.env.example). Файл `.env` в репозиторий не коммитится.

| Переменная | Обязательно | Описание |
| ---------- | ----------- | -------- |
| `MYSQL_ROOT_PASSWORD` | да | Пароль root MySQL |
| `MYSQL_DATABASE` | да | Имя БД WordPress |
| `MYSQL_USER` | да | Пользователь БД |
| `MYSQL_PASSWORD` | да | Пароль пользователя БД |
| `WP_HOME` | нет | URL сайта (по умолчанию `http://localhost:8081`) |
| `WP_SITEURL` | нет | URL WordPress (по умолчанию `http://localhost:8081`) |

### Полезные команды

```bash
# Логи
docker compose logs -f
docker compose logs -f nginx php84 mysql

# Статус
docker compose ps

# Shell в PHP 8.4
docker compose exec php84 bash

# MySQL CLI
docker compose exec mysql mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"

# Пересобрать образы PHP после смены Dockerfile
docker compose up -d --build php74 php84

# Повторно скачать ядро WP в пустой volume
docker volume rm cpm-dev_wp_data
docker compose run --rm wp-init
```

Логи стенда на хосте: `log/nginx/`, `log/php74/` (в т.ч. `wp-debug.log`), `log/php84/`.

---

## Структура репозитория

```
├── source/           # Код плагина (CPM_PLUGIN_DIR)
├── docs/             # Спецификации для разработки
├── tests/            # Стратегия и скаффолд PHPUnit
├── docker/           # Dockerfile PHP, init-wordpress.sh
├── conf/             # nginx и php.ini
├── www/              # Только wp-config.php (ядро WP — в volume wp_data)
├── log/              # Логи стенда
├── sql/              # Сервисные SQL для отладки
├── docker-compose.yml
├── .env.example
└── AGENTS.md
```

---

## Разработка

1. Спецификация класса/модуля в [`docs/`](docs/README.md) — до кода.
2. Реализация в `source/`.
3. Тесты по матрице [`tests/покрытие.md`](tests/покрытие.md).

Кратко по конвенциям — [`AGENTS.md`](AGENTS.md), стандарты кода — [`docs/09-стандарты-кодирования.md`](docs/09-стандарты-кодирования.md).

### Тесты (на стенде)

```bash
# Юнит
docker compose exec php84 bash -c "cd wp-content/plugins/cpm && vendor/bin/phpunit"

# Интеграционные (нужна тестовая БД cpm_test — см. tests/конфигурация.md)
docker compose exec php84 bash -c "cd wp-content/plugins/cpm && vendor/bin/phpunit -c phpunit-integration.xml.dist"
```

Подробности: [`tests/README.md`](tests/README.md).

---

## Устранение неполадок

| Симптом | Что проверить |
| ------- | ------------- |
| Пустая страница / 404 на 8081 | Дождаться `wp-init` (`docker compose ps`), в volume: `docker compose exec php84 ls index.php wp-includes` |
| Ошибка подключения к БД | Значения в `.env`, healthy у `mysql`, совпадение с `DB_*` в контейнере PHP |
| Порт занят | Другой процесс на 8080/8081/3306; сменить проброс в `docker-compose.yml` |
| Плагин не виден в WP | Mount `source/` → `wp-content/plugins/cpm`, права на каталог |
| Нужен «чистый» стенд | `docker compose down -v` (удалит `wp_data` и `db_data`), снова `up --build` |

---

## Документация

- [Карта спецификаций](docs/README.md)
- [Ядро](docs/core/README.md)
- [Тестирование](tests/README.md)
- [Инструкции для агентов](AGENTS.md)
