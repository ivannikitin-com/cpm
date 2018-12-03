<?php
/**
 * Класс менеджера расширений
 *
 * @package CPM/Extensions
 * @version 2.0.0
 */
namespace CPM\Extensions;

// Интерфейсы и базовые классы
require_once( dirname( __FILE__ ) . '/IExtension.php' );
require_once( dirname( __FILE__ ) . '/ExtensionBase.php' );

class Manager
{
	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Загрузим расширения
		$this->load();		
		
		// Инициализация расширений
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}
	
	// ------------------------------- Загрузчик расширений -------------------------------
	
	/**
	 * Загруженные расширения
	 * mixed IExtension
	 */	
	private $extensions;
	
	/**
	 * Загрузчик расширений
	 */
	private function load()
	{
		// Ассоциативный массив загруженных расширений
		$this->extensions = array();
		
		// Формируем массив расширений
		$extensionList = $this->getExtensionList();		
		foreach ( $extensionList as $extension )
		{
			// Имя файла без расширения
			$fileName = basename( $extension, '.php' );
			
			// Если имя файла начинается на "-" ничего не делаем
			if ( '-' == substr( $fileName, 0, 1 ) )
				continue;
			
			// Подключаем этот файл и проверяем класс
			try
			{
				require_once( $extension );
				// Имя класса и реализуемые им интерфейсы
				$class = __NAMESPACE__ . '\\' . $fileName;
				$interfaces = class_implements( $class );
				
				// Если реализуется интерфейс IExtension -- это наше расширение!
				if ( in_array( 'CPM\Extensions\IExtension', $interfaces ) )
				{
					// Создаем экземпляр класса
					$this->extensions[ $class ] = new $class();
				}
			}
			catch ( \Exception $e ) {}
		}
	}
	
	/**
	 * Метод возвращает массив с именами расширений
	 */
	private function getExtensionList()
	{
		$extensionList = array();
		
		// Читаем файлы
		$path = dirname( __FILE__ ) .'/..';
		$extensionList = glob( $path . '/*.php' );
		return apply_filters( 'cpm_extension_list', $extensionList );
	}	
	
	
	// ------------------------------- Инициализация расширений -------------------------------	
	
	/**
	 * Инициализация расширений
	 */
	public function init()
	{
		// Проинициализируем расширения
		foreach ( $this->extensions as $extension )
		{
			// Еще одна проверка на интерфейс
			if ( $extension instanceof IExtension ) 
			{
				// Инициализируем расширение
				$extension->init();
			}
		}
	}
}

// ------------------------ Демонстрация удаления расширения с загрузки ------------------------
add_filter( 'cpm_extension_list', __NAMESPACE__ . '\cpm_extension_list_demo' );
function cpm_extension_list_demo( $extensionList )		// Обратите внимание, функция определяется внутри пространства имен!!!
{
	//var_dump( $extensionList );	// В массиве польные пути, поэтому удалять лучге так:
	$extensionList = array_filter( $extensionList, function( $value ) {
			return strpos( $value, 'Example' ) === false;
		});	
	
	return $extensionList;
}