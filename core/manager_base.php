<?php
/**
 * Базовый класс для менеджеров модулей CPM
 */

 namespace CPM\Core;

abstract class ManagerBase {
    /**
     * Метод инициализации классов модуля
     * Должен быть перекрыт потомками
     */
    abstract public function init();
}