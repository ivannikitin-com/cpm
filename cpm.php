<?php
/**
 * Plugin Name: CPM
 * Plugin URI: https://github.com/ivannikitin-com/cpm
 * Description: Управление задачами и проектами
 * Author: Ivan Nikitin & Co
 * Author URI: https://ivannikitin.com
 * Version: 3.0.0
 * License: GPL2
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Глобальные определения
 */
define ( 'CPM', 'CPM' );

/**
 * Файлы плагина
 */
require_once __DIR__ . '/plugin.php';
require_once __DIR__ . '/core/member.php';
require_once __DIR__ . '/core/team.php';
require_once __DIR__ . '/core/entity.php';
require_once __DIR__ . '/core/project.php';
require_once __DIR__ . '/core/category.php';
require_once __DIR__ . '/view/front.php';

// Запуск CPM
\CPM\Plugin::getInstance();