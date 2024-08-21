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

    /**
     * Проверка файла модуля
     * Возвращает TRUE, если файл нужно загрузить через require
     */
    protected function is_module_file( $file_name ) {
        // Если в имени файла есть .view.php, то это интерфейс. Не загружаем его
        if ( strpos( $file_name, '.view.php' ) !== false ) {
            return false;
        }
        // Отдаем проверку родителю
        return parent::is_module_file( $file_name );
    }
}
