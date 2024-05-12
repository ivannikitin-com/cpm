<?php
/**
 * Класс для работы с участниками сущности
 * @author Ivan Nikitin
 */
namespace CPM\Core;

class Team
{
    /**
     * Список участников сущности
     * @var array
     */
    public $members = [];

    /**
     * Конструктор
     * @param array $members
     */
    public function __construct($members = [])
    {
        $this->members = $members;
    }

    /**
     * Добавление участника
     * @param Member $member
     * @return void 
     */
    public function add( $member )
    {
        $this->members[] = $member;
    }

    /**
     * Удаляет участника
     * @param Member $member
     */
    public function remove( $member ) {
        $key = array_search($member, $this->members);
        if ($key !== false) {
            unset($this->members[$key]);
        }
    }

    /**
     * Проверяет, пустая ли команда
     * @return bool
     */
    public function is_empty() {
        return empty($this->members);
    }
}