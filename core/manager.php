<?php
/**
 * Класс менеджера модуля CPM
 * 
 * Менеджер ядра обеспечивает загрузку и инициализацию классов CPM.
 * От этого класса наследуются менеджеры других модулей CPM.
 * 
 * @package CPM
 * @author Ivan Nikitin
 * @version 3.0
 */
namespace CPM\Core;

class Manager
{
    /**
     * Рабочая папка модуля CPM
     */
    protected $path = __DIR__ . '/';

    /**
     * Пространство имен модуля CPM
     */
    protected $namespace = __NAMESPACE__ . '\\';

    /**
     * Файлы и классы модуля CPM
     * array( файл => класс)
     * @var array
     */
    protected $files = array();

    /**
     * Конструктор менеджера
     */
    public function __construct() 
    {
        // Загрузка файлов модуля CPM
        $this->load();

        // Ранняя инициализация классов модуля CPM
        $this->late_init();
    }

    /**
     * Метод возвращает все загруженные файлы модуля CPM
     */
    public function get_files()
    {
        return array_keys( $this->files );
    }

    /**
     * Метод возвращает все имена классов модуля CPM
     */
    public function get_classes()
    {
        return array_values( $this->files );
    }

    /**
     * Загрузка файлов модуля CPM
     * Мы не используем (пока) автозагрузку, поэтому делается вручную
     * Метод помимо загрузки заполняет $this->files
     */
    protected function load()
    {
        $files = glob( $this->path . '*.php', GLOB_NOSORT);
              
        foreach ($files as $file) {
            // Если это файл менеджера, то пропускаем
            if ( 'manager.php' == basename( $file ) ) {
                continue;
            }

            // Файл
            require_once $file;
            
            // Имя класса -- это имя файла без пути и с большой буквы
            $class = basename($file, '.php');
            $class = $this->namespace . ucfirst($class);
            $this->files[$file] = $class;
        }
    }

    /**
     * Ранняя инициализация классов модуля CPM
     * Экземпляры классов модуля CPM здесь НЕ СОЗДАЮТСЯ
     */
    protected function late_init()
    {

        foreach ( $this->get_classes() as $class ) {
            $debug_log_string = static::class . ' loading class ' . $class;
            // Если у класса есть статичный метод init, то вызываем его
            if ( method_exists( $class, 'init' ) ) {
                $debug_log_string .= ' and calling init()';
                $class::init();
            }
            WP_DEBUG && error_log( $debug_log_string );
        }
    }
}