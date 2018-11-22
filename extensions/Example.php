<?php
/**
 * Пример класса расширения
 * Ничего не делает
 *
 * @package CPM/Extensions
 * @version 2.0.0
 */
namespace CPM\Extensions;

class Example implements IExtension
{
	/**
	 * Метод возвращает название расширения
	 * @return string
	 */
    public function getTitle()
	{
		return __( 'Пример расширения', 'cpm' );
	}
	
    /**
	 * Метод инициализирует расширение. В нем происходит установка всех хуков
	 */
	public function init()
	{
		// Демонстрация инициализации
		add_action( 'admin_notices', array( $this, 'showNotice' ) );
	}
	
	/**
	 * Метод показывает банер с уведомлением
	 * @return string
	 */
    public function showNotice()
	{
		echo '<div class="notice notice-success is-dismissible"><p>',
			__( 'Расширение загружено', 'cpm' ), 
			': ',
			$this->getTitle(),
			'</p></div>';
	}
}
