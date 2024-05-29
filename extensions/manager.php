<?php
/**
 * Класс управления расширениями CPM
 * 
 * Каждое расширение должно быть оформлено в отдельной папке
 * и папка должна содержать файл .php c названием этой папки
 * 
 * Каждое расширение это дочерний класс от базового \CPM\Extension\ExtensionBase
 */
namespace CPM\Extensions;

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
     * Загрузка файлов расширений
     * Мы не используем (пока) автозагрузку, поэтому делается вручную
     * Метод помимо загрузки заполняет $this->files
     */
    protected function load()
    {
        // Каждое расширение должно быть оформлено в отдельной папке
        $extensions = glob( $this->path . '*');
        foreach ($extensions as $extension) {
            if ( is_dir( $extension ) ) {
                // Название расширения
                $name = basename( $extension );

                // Файл расширения
                $file = $extension . '/' . $name . '.php';

                // Загрузка файла расширения
                require_once $file;                

                // Имя класса -- это имя расширения с большой буквы в пространстве имён с названием расширения
                // Например: CPM\Extensions\Myextension\Myextension
                // Имена должны быть односложными
                $class = __NAMESPACE__ . '\\' . ucfirst($name) . '\\' . ucfirst($name);
                $this->files[ $file ] = $class;
            }
        }
    }
}