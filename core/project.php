<?php

namespace CPM\Core;

class Project extends EntityAccess {
    /**
     * Имя CPT
     */
    const CPT = 'project';

    /* ------------------ Дополнительные свойства проекта ------------------ */

    /**
     * Категория проекта
     */
    public $category;

    /**
     * Статус проекта
     */
    public $status;


    /**
     * Метод загружает проект из БД, в том случае, если ID не нулевой 
     */
    public function load() {
        if ( empty( $this->id ) ) return; // ID не задано, ничего не делаем

        // Читаем метаданные WordPress для проекта СТАРОЙ ВЕРСИИ CPM
        

        // Загружаем из БД основные свойства
        parent::load();
    }
    
}
