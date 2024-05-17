<?php
/**
 * CPM REST API менеджер
 * Этот класс управляет инициализацией и контроллерами REST API
 */

namespace CPM\REST;

class Manager extends \CPM\Core\Manager
{
    /**
     * Конструктор менеджера
     */
    public function __construct() 
    {
        // Рабочая папка модуля
        $this->path = __DIR__ . '/';

        // Пространство имён модуля
        $this->namespace = __NAMESPACE__ . '\\';

        // Базовый конструктор
        parent::__construct();
    }
}
