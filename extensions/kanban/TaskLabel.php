<?php
/**
 * Метка задачи -- название секции канбан, в которой находится эта задача
 *
 * @package CPM/Extensions/Kanban
 * @version 2.0.0
 */
namespace CPM\Extensions\Kanban;

class TaskLabel 
{
	/**
	 * \CPM\Extensions\Kanban $kanban	Ссылка на родительский объект, например, для доступа к настройкам
	 */
	private $kanban;
	
    /**
	 * Конструктор класса
	 * 
	 * @param Kanban $kanbanExtension	Ссылка на родительский объект, например, для доступа к настройкам
	 */
	public function __construct( \CPM\Extensions\Kanban $kanbanExtension )
	{
		$this->kanban = $kanbanExtension;
	}	
	
    /**
	 * Метод инициализирует расширение. В нем происходит установка всех хуков
	 */
	public function init()
	{
		add_action( 'cpm_todo_content_after', array( $this, 'test' ), 10, 4 );
	}
	
	public function test()
	{
		echo 'TEST';
		
	}
}