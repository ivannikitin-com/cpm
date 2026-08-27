--
-- Пример вывода проектов с чтением team из старой таблицы и нового поля team
-- 
-- На выходе должна быть строка типа:
-- "12":manager,"15":co_worker,"22":co_worker,"33":client
--
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
	MAX(CASE WHEN pm.meta_key = 'team' THEN pm.meta_value ELSE NULL END),
	-- Старая схема участников
	(
		-- Чтение таблицы in_cpm_project_items для нужного типа записей
		SELECT
			GROUP_CONCAT(CONCAT('"', user_id, '":' , `role`))
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
HAVING
	TRUE
	-- Здесь могут быть дополнительные фильтры
ORDER BY
	menu_order DESC,
	post_title ASC
	
	
-- -----------------------------------------------------

SELECT 
	p.ID AS id,
	MAX(p.post_title) AS title,
	(
		(
			SELECT
				GROUP_CONCAT(CONCAT('"', user_id, '":', `role`))
			FROM
				in_cpm_user_role r
			WHERE
				r.project_id = p.ID
		)
	) AS test
FROM
	in_posts p
	INNER JOIN in_postmeta pm
		ON p.ID = pm.post_id	
WHERE
	p.post_type = 'cpm_project'
GROUP BY
	p.ID
	