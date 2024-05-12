<?php
/**
 * Класс участника сущности CPM
 */
namespace CPM\Core;

class Member
{
    /**
     * Идентификатор участника
     * @var int
     */
    public $ID;

    /**
     * Роль участника
     * @var string
     */
    public $role;

    /**
     * Конструктор
     * @param int $ID Идентификатор участника
     * @param string $role Роль участника
     * @return void
     */
    public function __construct($ID, $role)
    {
        $this->ID = $ID;
        $this->role = $role;
    }

}