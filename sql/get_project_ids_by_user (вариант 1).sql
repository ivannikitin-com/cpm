--
-- Полчение ID проектов по ID пользователя
--
-- Необходимо для получения списка объектов, к которым у пользователя есть доступ
--
-- Новая схема
SELECT
	ID AS id
FROM
	in_posts p
		INNER JOIN in_postmeta pm
			ON p.ID = pm.post_id
WHERE
	post_type = 'cpm_project'
	AND meta_key = '_team'
	AND meta_value LIKE '%"48"%'
UNION DISTINCT
-- Старая схема
SELECT 
	project_id AS id 
FROM 
	in_cpm_user_role 
WHERE 
	user_id = 48
