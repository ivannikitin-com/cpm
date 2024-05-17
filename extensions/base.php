<?php
/**
 * Базовый класс расширений CPM
 * Все расширения должны наследоваться от этого класса
 */

namespace CPM\Extensions;

abstract class Base
{
    /**
     * Имя расширения
     * @var static string
     */
    public static $title;
}