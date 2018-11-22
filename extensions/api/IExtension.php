<?php
/**
 * Интерфейс расширения CPM
 *
 * @package CPM/Extensions
 * @version 2.0.0
 */
namespace CPM\Extensions;

interface IExtension
{
    /**
	 * Метод инициализирует расширение. В нем происходит установка всех хуков
	 */
	public function init();
	
	/**
	 * Метод возвращает название расширения
	 * @return string
	 */
    public function getTitle();
}