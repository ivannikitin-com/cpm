<?php
/**
 * Базовый класс менеджера модуля CPM
 * Формирует интерфейс класса менеджера модуля CPM
 * и базовые методы менеджера
 */
namespace CPM;
class ManagerBase {
    /**
     * Функция инициализации
     * Вызывается плагином во время хука init
     */
    public function init() {
        Plugin::get_instance()->log( static::class . ' init', 'info' );
    }
}