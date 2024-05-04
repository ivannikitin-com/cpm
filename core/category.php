<?php
/**
 * Таксономия категории сущностей
 */
namespace CPM\Core;

class Category
{
    /**
     * Тип таксономии
     * Должно быть переопределено в наследнике
     */
    static public $CPT = 'cpm_category';

    /* -------------------- Инициализация ------------------- */
    public static function init()
    {
        // Регистрация таксономий
        register_taxonomy( self::$CPT, array( 'cpm_project' ), array(
            'label' => __( 'Категории' , CPM ),
            'labels' => array(
                'name' => __( 'Категории' , CPM ),
                'singular_name' => __( 'Категория' , CPM ),
                'search_items' =>  __( 'Искать категории' , CPM ),
                'popular_items' => __( 'Популярные категории' , CPM ),
                'all_items' => __( 'Все категории' , CPM ),
                'parent_item' => null,
                'parent_item_colon' => null,
                'edit_item' => __( 'Редактировать категорию' , CPM ),
                'update_item' => __( 'Обновить категорию' , CPM ),
                'add_new_item' => __( 'Добавить новую категорию' , CPM ),
                'new_item_name' => __( 'Новая категория' , CPM ),  
            )
        ) );
    }
}
