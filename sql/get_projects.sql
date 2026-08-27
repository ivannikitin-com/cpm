--
-- Получение списка проектов CPM v3
-- 
SELECT
	ID AS id,
	post_parent AS parent,
	post_author AS author,
	post_title AS title,
	post_content AS content,
	post_date AS created_at,
	post_name AS slug,
	menu_order,
	COALESCE(
		-- Новая схема участников
		MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END),
		-- Старая схема учатников
		(SELECT GROUP_CONCAT(CONCAT('"', user_id, '":' , `role`)) FROM in_cpm_user_role r WHERE project_id = p.id GROUP BY project_id)
	) AS team,
	MAX(CASE WHEN pm.meta_key = '_cpm_coordinator' THEN pm.meta_value ELSE NULL END) AS coordinator,
	MAX(CASE WHEN pm.meta_key = '_project_active' THEN pm.meta_value ELSE NULL END) AS status
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
	AND team LIKE '%"48"%' -- все проекты пользовтеля 48
ORDER BY
	menu_order DESC,
	post_title ASC