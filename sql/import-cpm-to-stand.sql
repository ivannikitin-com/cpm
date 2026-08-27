-- Перенос отфильтрованных данных CPM из cpm_export в БД стенда CPM.
-- wp_options / таксономии стенда не трогаем (siteurl=localhost:8081).
-- Админ стенда user_login=cpm сохраняется с новым ID, чтобы ID продакшена не сломались.

SET NAMES utf8mb4;
SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '');
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;
SET AUTOCOMMIT = 1;

USE `CPM`;

-- Сохраняем админа стенда до замены wp_users
DROP TEMPORARY TABLE IF EXISTS `_stand_admin_user`;
DROP TEMPORARY TABLE IF EXISTS `_stand_admin_meta`;
CREATE TEMPORARY TABLE `_stand_admin_user` AS
SELECT * FROM `CPM`.`wp_users` WHERE `user_login` = 'cpm';
CREATE TEMPORARY TABLE `_stand_admin_meta` AS
SELECT um.*
FROM `CPM`.`wp_usermeta` AS um
INNER JOIN `CPM`.`wp_users` AS u ON u.ID = um.user_id
WHERE u.user_login = 'cpm';

-- Контентные таблицы: схема как в экспорте (совместима с боевым дампом)
DROP TABLE IF EXISTS `CPM`.`wp_posts`;
CREATE TABLE `CPM`.`wp_posts` LIKE `cpm_export`.`wp_posts`;
INSERT INTO `CPM`.`wp_posts` SELECT * FROM `cpm_export`.`wp_posts`;

DROP TABLE IF EXISTS `CPM`.`wp_postmeta`;
CREATE TABLE `CPM`.`wp_postmeta` LIKE `cpm_export`.`wp_postmeta`;
INSERT INTO `CPM`.`wp_postmeta` SELECT * FROM `cpm_export`.`wp_postmeta`;

DROP TABLE IF EXISTS `CPM`.`wp_comments`;
CREATE TABLE `CPM`.`wp_comments` LIKE `cpm_export`.`wp_comments`;
INSERT INTO `CPM`.`wp_comments` SELECT * FROM `cpm_export`.`wp_comments`;

DROP TABLE IF EXISTS `CPM`.`wp_commentmeta`;
CREATE TABLE `CPM`.`wp_commentmeta` LIKE `cpm_export`.`wp_commentmeta`;
INSERT INTO `CPM`.`wp_commentmeta` SELECT * FROM `cpm_export`.`wp_commentmeta`;

DROP TABLE IF EXISTS `CPM`.`wp_users`;
CREATE TABLE `CPM`.`wp_users` LIKE `cpm_export`.`wp_users`;
INSERT INTO `CPM`.`wp_users` SELECT * FROM `cpm_export`.`wp_users`;

DROP TABLE IF EXISTS `CPM`.`wp_usermeta`;
CREATE TABLE `CPM`.`wp_usermeta` LIKE `cpm_export`.`wp_usermeta`;
INSERT INTO `CPM`.`wp_usermeta` SELECT * FROM `cpm_export`.`wp_usermeta`;

-- Легаси CPM
DROP TABLE IF EXISTS `CPM`.`wp_cpm_user_role`;
CREATE TABLE `CPM`.`wp_cpm_user_role` LIKE `cpm_export`.`wp_cpm_user_role`;
INSERT INTO `CPM`.`wp_cpm_user_role` SELECT * FROM `cpm_export`.`wp_cpm_user_role`;

DROP TABLE IF EXISTS `CPM`.`wp_cpm_tasks`;
CREATE TABLE `CPM`.`wp_cpm_tasks` LIKE `cpm_export`.`wp_cpm_tasks`;
INSERT INTO `CPM`.`wp_cpm_tasks` SELECT * FROM `cpm_export`.`wp_cpm_tasks`;

DROP TABLE IF EXISTS `CPM`.`wp_cpm_project_items`;
CREATE TABLE `CPM`.`wp_cpm_project_items` LIKE `cpm_export`.`wp_cpm_project_items`;
INSERT INTO `CPM`.`wp_cpm_project_items` SELECT * FROM `cpm_export`.`wp_cpm_project_items`;

DROP TABLE IF EXISTS `CPM`.`wp_cpm_file_relationship`;
CREATE TABLE `CPM`.`wp_cpm_file_relationship` LIKE `cpm_export`.`wp_cpm_file_relationship`;
INSERT INTO `CPM`.`wp_cpm_file_relationship` SELECT * FROM `cpm_export`.`wp_cpm_file_relationship`;

-- Возвращаем админа стенда с свободным ID (логин/пароль из .env)
SET @stand_admin_id = (SELECT IFNULL(MAX(ID), 0) + 1 FROM `CPM`.`wp_users`);

INSERT INTO `CPM`.`wp_users` (
	`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_url`,
	`user_registered`, `user_activation_key`, `user_status`, `display_name`
)
SELECT
	@stand_admin_id,
	`user_login`, `user_pass`, `user_nicename`, `user_email`, `user_url`,
	`user_registered`, `user_activation_key`, `user_status`, `display_name`
FROM `_stand_admin_user`;

INSERT INTO `CPM`.`wp_usermeta` (`user_id`, `meta_key`, `meta_value`)
SELECT @stand_admin_id, `meta_key`, `meta_value`
FROM `_stand_admin_meta`;

-- Плагин v3 пока каркас: не активируем (иначе fatal на init). Включить вручную, когда ядро поднимется.

-- Главная стенда больше не указывает на вытесненные page ID.
UPDATE `CPM`.`wp_options` SET `option_value` = 'posts' WHERE `option_name` = 'show_on_front';
UPDATE `CPM`.`wp_options` SET `option_value` = '0' WHERE `option_name` IN ('page_on_front', 'page_for_posts');

SET UNIQUE_CHECKS = 1;
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'stand posts' AS metric, post_type AS k, COUNT(*) AS n
FROM `CPM`.`wp_posts` GROUP BY post_type
UNION ALL
SELECT 'stand comments', IF(comment_type = '', '(empty)', comment_type), COUNT(*)
FROM `CPM`.`wp_comments` GROUP BY comment_type
UNION ALL
SELECT 'stand users', '', COUNT(*) FROM `CPM`.`wp_users`
UNION ALL
SELECT 'stand cpm_user_role', '', COUNT(*) FROM `CPM`.`wp_cpm_user_role`
UNION ALL
SELECT 'stand admin', user_login, ID FROM `CPM`.`wp_users` WHERE user_login = 'cpm'
ORDER BY metric, k;
