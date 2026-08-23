# Класс Message

Класс сущности «Сообщение (обсуждение) в проекте» (`\CPM\v3\Core\Message`). Отражает сообщение, обсуждение в проекте, не привязанное ни к каким другим сущностям. Наследует `Project_Entity`.

## Статичные свойства и методы

| Член | Значение/Назначение |
| ---- | ------------------- |
| `CPT` | `cpm_message` |
| `SQL` | SQL-запрос выборки сообщений |
| `register()` | Регистрация CPT |

## Свойства

Дополнительно к наследуемым свойствам:

| Свойство | Тип | Описание |
| -------- | --- | -------- |
| `message_privacy` | string | Признак приватности обсуждения («только для сотрудников»): `yes` / `no`. Мета-поле `_message_privacy` |
| `milestone` | int | ID вехи, к которой привязано обсуждение; `0` — веха не назначена. Мета-поле `_milestone` |
| `files` | array | Массив ID вложений (attachment), прикреплённых к обсуждению. Мета-поле `_files` (сериализованный массив), например `a:1:{i:0;s:4:"6932";}` |

### Вложения обсуждения (`files`)

Файлы, прикреплённые к обсуждению, хранятся двумя связанными способами (подтверждено реальной БД и кодом старого плагина):

1. **Мета-поле `_files` у обсуждения** — сериализованный массив ID вложений (записей типа `attachment`). Это «верхнеуровневый» список файлов сообщения.
2. **Привязка самих вложений** — каждое вложение связано с обсуждением через `post_parent` (равен ID сообщения) и мета-поля самого вложения: `_project` (ID проекта) и `_parent` (ID обсуждения).

Такая двойная связь (см. `associate_file()` в старом коде) позволяет:

- быстро получать файлы конкретного обсуждения через `post_parent` / мета `_parent`;
- находить все вложения проекта через мета `_project`.

При `delete_entity()` обсуждения эти вложения **остаются** в медиабиблиотеке (как и для featured image): прикреплённые файлы могут переиспользоваться. Удаление самих attachment-записей не выполняется.

> **Поле `files` не выбирается в SQL**: массив вложений `_files` хранится в сериализованном виде и не участвует в фильтрации, поэтому в основной SQL-выборке не читается. Файлы обсуждения загружаются отдельно по запросу (через `post_parent` / мета `_parent` у вложений).

### Команда обсуждения (Team)

Свойство `team` хранит команду (участников) обсуждения. Это дефолтовые значения — кому отправлять уведомления по умолчанию.

## SQL-запрос сообщений

Запрос хранится в статичном свойстве `SQL` и используется `Core_Manager`. Подробности чтения прав — `чтение-участников-sql.md`.

> **Прототип SQL.** В реальном коде — параметризованный запрос `$wpdb->prepare()`, имена таблиц `$wpdb->posts` / `$wpdb->postmeta`. Условие `( is_admin OR team LIKE ... )` формируется динамически. Подробнее: `выборка-sql.md`.

```sql
--
-- Получение сообщений в проекте CPM v3
--
SELECT
	-- Данные Entity
	p.ID AS id,
	post_parent AS parent,
	post_author AS author,
	post_title AS title,
	post_content AS content,
	post_date AS created_at,
	post_name AS slug,
	menu_order,
	MAX(CASE WHEN pm.meta_key = '_thumbnail_id' THEN pm.meta_value ELSE NULL END) AS thumbnail_id,
	MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END) AS team,
	-- Данные Project_Entity
	project.id AS project_id,
	project.title AS project_title,
	project.slug AS project_slug,
	-- Данные Message
	MAX(CASE WHEN pm.meta_key = '_milestone' THEN pm.meta_value ELSE NULL END) AS milestone,
	MAX(CASE WHEN pm.meta_key = '_message_privacy' THEN pm.meta_value ELSE NULL END) AS message_privacy
FROM
	in_posts p
		INNER JOIN in_postmeta pm
			ON p.ID = pm.post_id
		INNER JOIN (
			-- Проекты пользователя
			SELECT DISTINCT
				id,
				title,
				slug
			FROM (
				-- Проекты с участниками
				SELECT
					p.ID AS id,
					post_title AS title,
					post_name AS slug,
					COALESCE(
						-- Новая схема участников
						CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END,
						-- Старая схема участников
						(SELECT GROUP_CONCAT(CONCAT('"', user_id, '":', `role`)) FROM in_cpm_user_role r WHERE project_id = p.id GROUP BY project_id)
					) AS team
				FROM
					in_posts p
						INNER JOIN in_postmeta pm
							ON p.ID = pm.post_id
				WHERE
					post_type = 'cpm_project'
			) projects
			WHERE
				-- is_admin -- параметр запроса, true для администратора (фильтр по участнику не применяется)
				( is_admin OR team LIKE '%"277"%' ) -- '%"277"%' -- фильтр по участнику (ID текущего пользователя)
		) project
			ON p.post_parent = project.id
WHERE
	post_type = 'cpm_message'
GROUP BY
	id
HAVING
	TRUE
	-- Здесь могут быть любые дополнительные фильтры, например:
	-- AND project_id = 6583     -- Проект 6583
	-- AND slug = 'my-project'   -- Проект со слагом my-project
```
