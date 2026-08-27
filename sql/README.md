# SQL-скрипты

Сервисная папка для SQL-скриптов и их отладки. Файлы из этой папки в коде плагина не используются.

## `export-cpm.sql`

Выборка данных CPM из полного дампа боевой БД `ivannikitin` (префикс `in_`) в отдельную БД `cpm_export` (префикс `wp_`, как на Docker-стенде).

Копируются все типы ядра из `docs/core/`:

| Сущность | Откуда |
| -------- | ------ |
| Project | `cpm_project` |
| Task_List | `cpm_task_list` |
| Task | `cpm_task` |
| Milestone | `cpm_milestone` |
| Message | `cpm_message` |
| Note | `cpm_docs` |
| Attachment | `attachment` с привязкой к CPM |
| Comment | `comments`, тип `comment` |
| Activity | `comments`, тип `cpm_activity` |
| Team / роли | `wp_cpm_user_role` + пользователи |

Перед запуском полный дамп должен лежать в БД `ivannikitin`, **не** в БД стенда.

## `import-cpm-to-stand.sql`

Перенос из `cpm_export` в БД стенда `CPM`. Сохраняет `wp_options` стенда (`siteurl` / `home`) и админа `cpm` (новый ID). Плагин не активируется — каркас v3 сейчас падает на `init`.
