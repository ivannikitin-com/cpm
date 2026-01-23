<?php
/**
 * Менеджер ядра CPM
 * Отвечает за загрузку и инициализацию классов ядраCPM
 */
namespace CPM\Core;

class Manager {
    public function __construct() {
        // Загрузка классов ядра CPM
        require_once( CPM_PATH . '/classes/core/entity.php' );
    }

    /**
     * Функция инициализации
     * Вызывается плагином во время хука init
     */
    public function init() {
        parent::init();
    }
}