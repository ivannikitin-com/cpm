<?php
/**
 * CPM менеджер интерфейсов
 * Этот класс управляет инициализацией классов интерфейса
 */

namespace CPM\Views;

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
