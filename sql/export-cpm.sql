-- =============================================================================
-- Экспорт данных CPM из полного дампа WordPress в отдельную БД для стенда.
--
-- Источник правды по типам: docs/core/ (проверка-старых-данных.md, мета-поля.md,
-- совместимость.md) и константы CPT классов ядра.
--
-- Что попадает
--   CPT:        cpm_project, cpm_task_list, cpm_task, cpm_milestone,
--               cpm_message, cpm_docs (Note)
--   Attachment: записи attachment, связанные с CPM (_project / post_parent).
--               Сами файлы uploads/ не копируются.
--   Комментарии: comment_type IN ('comment', '', 'cpm_activity') к этим записям
--   Пользователи: авторы, участники, координаторы, assigned, авторы комментариев
--   Легаси:     {prefix}cpm_user_role — обязательна для чтения v3 (совместимость)
--
-- Что не попадает
--   CPT activity — это WeDevs Project Manager, не ядро CPM v3 (activity.md)
--   Обычные записи post/page, WooCommerce, MailPoet, Yoast и прочие плагины
--   Комментарии к блогу (comment_post_ID → post), даже если они есть в дампе
--
-- Легаси-таблицы v2 (v3 их не читает, копируются для отладки совместимости):
--   cpm_tasks, cpm_project_items, cpm_file_relationship
--
-- Префиксы
--   Источник (боевой дамп):  БД ivannikitin, таблицы in_*
--   Приёмник (экспорт):      БД cpm_export,  таблицы wp_*  (как на Docker-стенде)
--
-- Как запускать (после загрузки полного дампа в БД ivannikitin, НЕ в БД стенда):
--
--   mysql -h 127.0.0.1 -P 3306 -u root -p < sql/export-cpm.sql
--
--   mysqldump -h 127.0.0.1 -P 3306 -u root -p --single-transaction --routines \
--     cpm_export | gzip -c > backup/cpm-types.sql.gz
-- =============================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '');
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;
SET AUTOCOMMIT = 1;

CREATE DATABASE IF NOT EXISTS `cpm_export`
	CHARACTER SET utf8mb4
	COLLATE utf8mb4_unicode_ci;

USE `cpm_export`;

-- -----------------------------------------------------------------------------
-- Схемы таблиц (копия структуры боевого дампа, имена под префикс стенда)
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `cpm_export`.`wp_posts`;
DROP TABLE IF EXISTS `cpm_export`.`wp_postmeta`;
DROP TABLE IF EXISTS `cpm_export`.`wp_comments`;
DROP TABLE IF EXISTS `cpm_export`.`wp_commentmeta`;
DROP TABLE IF EXISTS `cpm_export`.`wp_users`;
DROP TABLE IF EXISTS `cpm_export`.`wp_usermeta`;
DROP TABLE IF EXISTS `cpm_export`.`wp_cpm_user_role`;
DROP TABLE IF EXISTS `cpm_export`.`wp_cpm_tasks`;
DROP TABLE IF EXISTS `cpm_export`.`wp_cpm_project_items`;
DROP TABLE IF EXISTS `cpm_export`.`wp_cpm_file_relationship`;

CREATE TABLE `cpm_export`.`wp_posts` LIKE `ivannikitin`.`in_posts`;
CREATE TABLE `cpm_export`.`wp_postmeta` LIKE `ivannikitin`.`in_postmeta`;
CREATE TABLE `cpm_export`.`wp_comments` LIKE `ivannikitin`.`in_comments`;
CREATE TABLE `cpm_export`.`wp_commentmeta` LIKE `ivannikitin`.`in_commentmeta`;
CREATE TABLE `cpm_export`.`wp_users` LIKE `ivannikitin`.`in_users`;
CREATE TABLE `cpm_export`.`wp_usermeta` LIKE `ivannikitin`.`in_usermeta`;
CREATE TABLE `cpm_export`.`wp_cpm_user_role` LIKE `ivannikitin`.`in_cpm_user_role`;
CREATE TABLE `cpm_export`.`wp_cpm_tasks` LIKE `ivannikitin`.`in_cpm_tasks`;
CREATE TABLE `cpm_export`.`wp_cpm_project_items` LIKE `ivannikitin`.`in_cpm_project_items`;
CREATE TABLE `cpm_export`.`wp_cpm_file_relationship` LIKE `ivannikitin`.`in_cpm_file_relationship`;

-- -----------------------------------------------------------------------------
-- 1. CPT сущностей ядра (docs/core/*.md)
-- -----------------------------------------------------------------------------

INSERT INTO `cpm_export`.`wp_posts`
SELECT *
FROM `ivannikitin`.`in_posts`
WHERE `post_type` IN (
	'cpm_project',
	'cpm_task_list',
	'cpm_task',
	'cpm_milestone',
	'cpm_message',
	'cpm_docs'
);

-- -----------------------------------------------------------------------------
-- 2. Вложения CPM (класс Attachment)
--    Берём по мета _project (основная привязка, ~24k из 27k в боевой БД)
--    и по post_parent на уже скопированную сущность CPM.
--    Разбор commentmeta._files в SQL намеренно не делается: это PHP-serialize,
--    коррелированный LIKE по 150k комментариев занял бы часы. Файлы комментариев
--    в боевой БД почти всегда уже имеют _project.
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO `cpm_export`.`wp_posts`
SELECT p.*
FROM `ivannikitin`.`in_posts` AS p
INNER JOIN `ivannikitin`.`in_postmeta` AS pm
	ON pm.post_id = p.ID
		AND pm.meta_key = '_project'
		AND pm.meta_value IS NOT NULL
		AND pm.meta_value <> ''
		AND pm.meta_value <> '0'
WHERE p.post_type = 'attachment';

INSERT IGNORE INTO `cpm_export`.`wp_posts`
SELECT p.*
FROM `ivannikitin`.`in_posts` AS p
INNER JOIN `cpm_export`.`wp_posts` AS e
	ON e.ID = p.post_parent
WHERE p.post_type = 'attachment';

-- -----------------------------------------------------------------------------
-- 3. Мета записей (включая _assigned, _team, _project, _files сообщений и т.д.)
-- -----------------------------------------------------------------------------

INSERT INTO `cpm_export`.`wp_postmeta`
SELECT pm.*
FROM `ivannikitin`.`in_postmeta` AS pm
INNER JOIN `cpm_export`.`wp_posts` AS p
	ON p.ID = pm.post_id;

-- -----------------------------------------------------------------------------
-- 4. Комментарии к сущностям CPM и лента активности (Comment + Activity)
-- -----------------------------------------------------------------------------

INSERT INTO `cpm_export`.`wp_comments`
SELECT c.*
FROM `ivannikitin`.`in_comments` AS c
INNER JOIN `cpm_export`.`wp_posts` AS p
	ON p.ID = c.comment_post_ID
WHERE c.comment_type IN ('comment', '', 'cpm_activity');

INSERT INTO `cpm_export`.`wp_commentmeta`
SELECT cm.*
FROM `ivannikitin`.`in_commentmeta` AS cm
INNER JOIN `cpm_export`.`wp_comments` AS c
	ON c.comment_ID = cm.comment_id;

-- -----------------------------------------------------------------------------
-- 5. Легаси-таблица ролей — обязательна для COALESCE() в SQL ядра
-- -----------------------------------------------------------------------------

INSERT INTO `cpm_export`.`wp_cpm_user_role`
SELECT *
FROM `ivannikitin`.`in_cpm_user_role`;

-- Остальные кастомные таблицы v2 (ядро v3 не читает)
INSERT INTO `cpm_export`.`wp_cpm_tasks`
SELECT * FROM `ivannikitin`.`in_cpm_tasks`;

INSERT INTO `cpm_export`.`wp_cpm_project_items`
SELECT * FROM `ivannikitin`.`in_cpm_project_items`;

INSERT INTO `cpm_export`.`wp_cpm_file_relationship`
SELECT * FROM `ivannikitin`.`in_cpm_file_relationship`;

-- -----------------------------------------------------------------------------
-- 6. Пользователи, на которых ссылаются сущности / роли / комментарии
-- -----------------------------------------------------------------------------

CREATE TEMPORARY TABLE `cpm_user_ids` (
	`ID` BIGINT UNSIGNED NOT NULL PRIMARY KEY
) ENGINE=Memory;

INSERT IGNORE INTO `cpm_user_ids` (`ID`)
SELECT DISTINCT `post_author`
FROM `cpm_export`.`wp_posts`
WHERE `post_author` > 0;

INSERT IGNORE INTO `cpm_user_ids` (`ID`)
SELECT DISTINCT `user_id`
FROM `cpm_export`.`wp_comments`
WHERE `user_id` > 0;

INSERT IGNORE INTO `cpm_user_ids` (`ID`)
SELECT DISTINCT `user_id`
FROM `cpm_export`.`wp_cpm_user_role`
WHERE `user_id` > 0;

INSERT IGNORE INTO `cpm_user_ids` (`ID`)
SELECT DISTINCT CAST(`meta_value` AS UNSIGNED)
FROM `cpm_export`.`wp_postmeta`
WHERE `meta_key` IN ('_cpm_coordinator', '_completed_by', '_assigned')
	AND `meta_value` REGEXP '^[0-9]+$'
	AND CAST(`meta_value` AS UNSIGNED) > 0;

INSERT IGNORE INTO `cpm_user_ids` (`ID`)
SELECT DISTINCT `created_by`
FROM `cpm_export`.`wp_cpm_file_relationship`
WHERE `created_by` > 0;

INSERT INTO `cpm_export`.`wp_users`
SELECT u.*
FROM `ivannikitin`.`in_users` AS u
INNER JOIN `cpm_user_ids` AS ids
	ON ids.ID = u.ID;

INSERT INTO `cpm_export`.`wp_usermeta`
SELECT um.*
FROM `ivannikitin`.`in_usermeta` AS um
INNER JOIN `cpm_export`.`wp_users` AS u
	ON u.ID = um.user_id;

DROP TEMPORARY TABLE `cpm_user_ids`;

SET UNIQUE_CHECKS = 1;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Контрольные счётчики
-- -----------------------------------------------------------------------------

SELECT 'posts by type' AS metric, post_type AS k, COUNT(*) AS n
FROM `cpm_export`.`wp_posts`
GROUP BY post_type
UNION ALL
SELECT 'comments by type', IF(comment_type = '', '(empty)', comment_type), COUNT(*)
FROM `cpm_export`.`wp_comments`
GROUP BY comment_type
UNION ALL
SELECT 'postmeta', '', COUNT(*) FROM `cpm_export`.`wp_postmeta`
UNION ALL
SELECT 'commentmeta', '', COUNT(*) FROM `cpm_export`.`wp_commentmeta`
UNION ALL
SELECT 'users', '', COUNT(*) FROM `cpm_export`.`wp_users`
UNION ALL
SELECT 'usermeta', '', COUNT(*) FROM `cpm_export`.`wp_usermeta`
UNION ALL
SELECT 'cpm_user_role', '', COUNT(*) FROM `cpm_export`.`wp_cpm_user_role`
UNION ALL
SELECT 'cpm_tasks (legacy)', '', COUNT(*) FROM `cpm_export`.`wp_cpm_tasks`
UNION ALL
SELECT 'cpm_project_items (legacy)', '', COUNT(*) FROM `cpm_export`.`wp_cpm_project_items`
UNION ALL
SELECT 'cpm_file_relationship (legacy)', '', COUNT(*) FROM `cpm_export`.`wp_cpm_file_relationship`
ORDER BY metric, k;
