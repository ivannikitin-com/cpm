<?php
/**
 * Class AccessDeniedException.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Пользователю запрещено действие над сущностью (декоратор).
 *
 * Спецификация: docs/core/ошибки.md
 */
class AccessDeniedException extends CPM_Exception {}
