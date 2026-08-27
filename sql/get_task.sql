--
-- Получение задач в проекте CPM v3
--
SELECT
	-- Данные Entity
	p.ID AS id,
	MAX(p.post_parent) AS parent,
	MAX(p.post_author) AS author,
	MAX(p.post_title) AS title,
	MAX(p.post_content) AS content,
	MAX(p.post_date) AS created_at,
	MAX(p.post_name) AS slug,
	MAX(p.menu_order) AS menu_order,
	-- Данные Project_Entity
	MAX(task_list.project_id) AS project_id,
	MAX(task_list.project_title) AS project_title,
	MAX(task_list.project_slug) AS project_slug,
	-- Данные Task
	MAX(task_list.task_list_id) AS task_list_id,
	MAX(task_list.task_list_title) AS task_list_title,
	MAX(task_list.task_list_slug) AS task_list_slug,
	MAX(CASE WHEN pm.meta_key = '_start' THEN pm.meta_value ELSE NULL END) AS start,
	MAX(CASE WHEN pm.meta_key = '_due' THEN pm.meta_value ELSE NULL END) AS due,
	MAX(CASE WHEN pm.meta_key = '_completed' THEN pm.meta_value ELSE NULL END) AS completed,
	MAX(CASE WHEN pm.meta_key = '_completed_on' THEN pm.meta_value ELSE NULL END) AS completed_on,
	MAX(CASE WHEN pm.meta_key = '_completed_by' THEN pm.meta_value ELSE NULL END) AS completed_by,
	MAX(CASE WHEN pm.meta_key = '_task_privacy' THEN pm.meta_value ELSE NULL END) AS task_privacy,
	-- Реализация team с обратной совместимостью
	COALESCE(
		-- Новая схема участников задачи
		MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END),
		-- Старая схема участников задачи
		GROUP_CONCAT(CASE WHEN pm.meta_key = '_assigned' THEN CONCAT('"', pm.meta_value, '":""') ELSE NULL END)
	) AS team
FROM
	in_posts p
		INNER JOIN in_postmeta pm
			ON p.ID = pm.post_id
		INNER JOIN (
			-- Списки задач
			SELECT DISTINCT
				p_task_list.id AS task_list_id,
				p_task_list.post_title AS task_list_title,
				p_task_list.post_name AS task_list_slug,
				p_task_list.post_parent AS task_list_parent,
				project.id AS project_id,
				project.title AS project_title,
				project.slug AS project_slug 
			FROM 
				in_posts p_task_list 
					INNER JOIN 	(
						-- Проекты текущего пользователя
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
						) all_projects
						WHERE
							-- Фильтрация проектов пользователя
							-- is_admin OR  
							team LIKE '%"277"%' -- все проекты пользовтеля 277		
					) project ON
						p_task_list.post_parent = project.id
			WHERE
				post_type = 'cpm_task_list'	
		) task_list 
			ON p.post_parent = task_list.task_list_id
WHERE
	post_type = 'cpm_task'
GROUP BY
	id
HAVING
	TRUE
	-- Здесь могут быть любые дополнительные фильтры, например:
	-- AND project_id = 6583     -- Проект 6583
	-- AND project_slug = 'prestige-tm-ru'   -- Проект со слагом prestige-tm-ru