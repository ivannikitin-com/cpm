<?php
/**
 * Class BadUserException.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Пользователь не найден при загрузке User::load().
 *
 * Спецификация: docs/core/ошибки.md
 */
class BadUserException extends CPM_Exception {}
