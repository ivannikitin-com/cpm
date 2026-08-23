# Класс Note

Класс сущности «Записка» (`\CPM\v3\Core\Note`). Отражает заметку в проекте CPM. Визуально — жёлтые стикеры или заметки (как в Basecamp) с некой информацией в проекте, либо ссылка на внешний документ (например, Google Docs). Наследует `Project_Entity`.

> **Это НЕ Message.** Класс `Note` не следует путать с `Message` (обсуждения в проекте). В старом коде CPM:
> - `cpm_message` — обсуждения (темы сообщений, не привязанные к задачам);
> - `cpm_docs` — записки/документы (Note).
>
> Это два разных типа записей. **CPT заметки — `cpm_docs`** (подтверждено реальной БД).

## Статичные свойства и методы

| Член | Значение/Назначение |
| ---- | ------------------- |
| `CPT` | `cpm_docs` |
| `SQL` | SQL-запрос выборки записок |
| `register()` | Регистрация CPT |

## Свойства

Дополнительно к наследуемым свойствам:

| Свойство | Тип | Описание |
| -------- | --- | -------- |
| `doc_type` | string | Тип заметки: `_custom_doc` — обычная заметка с текстовым содержимым; `_google_doc` — ссылка на внешний документ (например, Google Docs). Мета-поле `_doc_type` |

### Связь с проектом

Заметка принадлежит проекту: свойство `parent` указывает на ID проекта. Это подтверждается мета-полем `_project_uploaded`, значение которого совпадает с `post_parent` и указывает на запись типа `cpm_project`.

### Команда (Team)

Свойство `team` **не используется**: у заметки нет собственной команды участников. Доступ к заметке определяется командой проекта, поэтому в SQL-запросе поле `team` выбирается не для свойства заметки, а для фильтрации по команде проекта (доступ пользователя).

## SQL-запрос записок

Запрос хранится в статичном свойстве `SQL` и используется `Core_Manager`. Подробности чтения прав — `чтение-участников-sql.md`.

> **Прототип SQL.** В реальном коде — параметризованный запрос `$wpdb->prepare()`, имена таблиц `$wpdb->posts` / `$wpdb->postmeta`. Условие `( is_admin OR team LIKE ... )` формируется динамически. Подробнее: `выборка-sql.md`.

```sql
--
-- Получение записок (заметок) в проекте CPM v3
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
	-- Данные Project_Entity
	project.id AS project_id,
	project.title AS project_title,
	project.slug AS project_slug,
	-- Данные Note
	MAX(CASE WHEN pm.meta_key = '_doc_type' THEN pm.meta_value ELSE NULL END) AS doc_type
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
	post_type = 'cpm_docs'
GROUP BY
	id
HAVING
	TRUE
	-- Здесь могут быть любые дополнительные фильтры, например:
	-- AND project_id = 6583     -- Проект 6583
	-- AND slug = 'my-project'   -- Проект со слагом my-project
```
