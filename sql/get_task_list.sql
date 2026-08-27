--
-- Получение списков задач CPM v3
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
	MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END) AS team,
	-- Данные Project_Entity
	project.id AS project_id,
	project.title AS project_title,
	project.slug AS project_slug,
	-- Данные Task_List
	MAX(CASE WHEN pm.meta_key = '_milestone' THEN pm.meta_value ELSE NULL END) AS milestone
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
						-- Старая схема учатников
						(SELECT GROUP_CONCAT(CONCAT('"', user_id, '":' , `role`)) FROM in_cpm_user_role r WHERE project_id = p.id GROUP BY project_id)
					) AS team
				FROM
					in_posts p
						INNER JOIN in_postmeta pm
							ON p.ID = pm.post_id
				WHERE
					post_type = 'cpm_project'
			) projects
			WHERE
				-- Фильтрация проектов пользователя
				-- is_admin OR  
				team LIKE '%"277"%' -- все проекты пользовтеля 277		
		) project 
			ON p.post_parent = project.id
WHERE
	post_type = 'cpm_task_list'
GROUP BY
	id
HAVING
	TRUE
	-- Здесь могут быть любые дополнительные фильтры, например:
	-- AND project_id = 6583     -- Проект 6583
	-- AND slug = 'my-project'   -- Проект со слагом my-project