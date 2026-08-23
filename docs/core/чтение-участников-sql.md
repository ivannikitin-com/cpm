# Чтение участников CPM SQL запросом

Основная идея — одним запросом SQL получить все требуемые сущности (проекты, задачи и т.п.) с колонкой для формирования объекта `Team` в виде строки:

```
"12":manager,"15":co_worker,"22":co_worker,"33":client
```

Основная проблема в том, что в старой версии CPM эти данные хранятся в отдельной таблице `in_cpm_user_role`, а в новой версии — в виде мета-поля `_team`. При чтении должен отдаваться **приоритет мета-полю**, то есть данным новой версии.

## Предполагаемый фрагмент SQL (для проектов)

> **Прототип SQL.** В реальном коде — параметризованный запрос `$wpdb->prepare()` и имена таблиц `$wpdb->posts` / `$wpdb->postmeta`.

```sql
SELECT
	ID AS id,
	post_title AS title,
	-- post_content AS content,
	-- post_author AS author,
	-- post_date AS created_date,
	-- post_name AS slug,
	-- menu_order,
	-- MAX(CASE WHEN pm.meta_key = '_cpm_coordinator' THEN pm.meta_value ELSE NULL END) AS coordinator,
	COALESCE(
		-- Новая схема участников
		MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END),
		-- Старая схема участников
		(
			-- Чтение таблицы in_cpm_user_role для нужного типа записей
			SELECT
				GROUP_CONCAT(CONCAT('"', user_id, '":', `role`))
			FROM
				in_cpm_user_role r
			WHERE
				project_id = p.id
			GROUP BY
				project_id
		)
	) AS team
FROM
	in_posts p
		INNER JOIN in_postmeta pm
			ON p.ID = pm.post_id
WHERE
	post_type = 'cpm_project'
GROUP BY
	ID
ORDER BY
	menu_order DESC,
	post_title ASC
```

Для других сущностей в старой версии пользователи хранятся иначе — нет нужды делать подзапрос в `in_cpm_user_role`:

- **Задачи**: старые участники — в мета-полях `_assigned` (несколько записей postmeta на задачу, по одной на пользователя); SQL собирает их через `GROUP_CONCAT` и конвертирует в формат team. См. `task.md`.
- **Прочие сущности**: старое хранение не требуется — применяется только `_team`.
