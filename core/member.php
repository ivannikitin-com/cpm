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
     * Имя участника
     * @var string
     */
    public $name;

    /**
     * Электронный адрес участника
     * @var string
     */
    public $email;

    /**
     * Роль участника
     * @var string
     */
    public $role;
}