<?php
/**
 * Класс собой расширение для отчёта по сотрудникам
 */
namespace CPM\Extensions\Report;
require_once __DIR__ . '/../base.php';

class Report extends \CPM\Extensions\Base
{
    /**
     * Имя расширения
     * @var static string
     */
    public static $title = 'Отчёт сотрудника';

    /**
     * Статичный метод ранней инициализации расширения
     * Выполняется по init
     */
    public static function init() 
    {
        // Инициализируем сущность        
    }    
}
