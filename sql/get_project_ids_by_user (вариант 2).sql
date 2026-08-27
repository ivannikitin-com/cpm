--
-- Подзапрос получение списка ID проектов по пользователю
-- Этот подзапрос используется в запросах других элементов проекта
--
SELECT DISTINCT
	*
FROM (
	-- Проекты с участниками
	SELECT
		ID AS id,
		post_title AS title,
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
	team LIKE '%"277"%' -- все проекты пользовтеля 277
