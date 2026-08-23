# Класс Milestone

Класс сущности «Веха проекта» (`\CPM\v3\Core\Milestone`). Отражает веху (группу списков задач) в проекте CPM. Наследует `Project_Entity`.

## Статичные свойства и методы

| Член | Значение/Назначение |
| ---- | ------------------- |
| `CPT` | `cpm_milestone` |
| `SQL` | SQL-запрос выборки вех |
| `register()` | Регистрация CPT |

## Свойства

Дополнительно к наследуемым свойствам:

| Свойство | Тип | Описание |
| -------- | --- | -------- |
| `due` | string | Срок (дата) для этой вехи, например `2016-11-14 12:00:00` или пусто. Мета-поле `_due` |
| `completed` | int | Флаг завершённой вехи: `0` — не завершено, `1` — завершено. Мета-поле `_completed` |

### Команда вехи (Team)

Свойство `team` хранит команду (участников) вехи. Используется как **дефолтовые значения команды (участников) задачи** при создании нового списка задач.

## SQL-запрос вех

Запрос хранится в статичном свойстве `SQL` и используется `Core_Manager`. Подробности чтения прав — `чтение-участников-sql.md`.

> **Прототип SQL.** В реальном коде — параметризованный запрос `$wpdb->prepare()`, имена таблиц `$wpdb->posts` / `$wpdb->postmeta`. Условие `( is_admin OR team LIKE ... )` формируется динамически. Подробнее: `выборка-sql.md`.

```sql
--
-- Получение вех проектов CPM v3
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
	-- Данные Milestone
	MAX(CASE WHEN pm.meta_key = '_due' THEN pm.meta_value ELSE NULL END) AS due,
	MAX(CASE WHEN pm.meta_key = '_completed' THEN pm.meta_value ELSE NULL END) AS completed
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
	post_type = 'cpm_milestone'
GROUP BY
	id
HAVING
	TRUE
	-- Здесь могут быть любые дополнительные фильтры, например:
	-- AND project_id = 6583     -- Проект 6583
	-- AND slug = 'my-project'   -- Проект со слагом my-project
```
