<?php
/**
 * Исключения ядра CPM
 */

namespace CPM\Core;

/**
 * Базовое исключение ядра CPM
 * Нужно для возможного расширения
 */
class CPMException extends \Exception {
    /**
     * @var int ID сущности, в которой возникло исключение
     */
    public $entity_id;

    /**
     * Конструктор
     * @param string $message Сообщение исключения
     * @param int $entity_id ID сущности, в которой возникло исключение
     * @param int $code Код исключения
     */
    public function __construct($message, $entity_id = 0, $code = 0) {
        $this->entity_id = $entity_id;
        parent::__construct($message . ' (ID: ' . $entity_id . ')', $code = 0);
    }    
} 

/**
 * Исключение, возникающее при попытке получить из БД несуществующий элемент
 */
class ItemNotFoundException extends CPMException {}

/**
 * Исключение, возникающее при попытке получить из БД несуществующий элемент
 */
class EntityAccessException extends CPMException {}