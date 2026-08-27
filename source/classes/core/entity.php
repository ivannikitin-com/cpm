<?php
/**
 * Interface IEntity и класс Entity.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Контракт сущностей ядра CPM.
 *
 * Спецификация: docs/core/entity.md
 */
interface IEntity {

	/**
	 * Возвращает проект, которому принадлежит сущность, или пусто.
	 *
	 * @return Project|null
	 */
	public function get_project();

	/**
	 * Сохраняет объект в БД.
	 */
	public function save();

	/**
	 * Удаляет объект из БД.
	 *
	 * @param bool $is_recursion Признак вложенного шага рекурсивного удаления.
	 */
	public function delete( $is_recursion = false );
}

/**
 * Базовый абстрактный класс сущностей CPM v3.
 *
 * Спецификация: docs/core/entity.md
 */
abstract class Entity implements IEntity {

	/**
	 * Идентификатор сущности.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * ID родительского элемента.
	 *
	 * @var int
	 */
	public $parent = 0;

	/**
	 * ID автора сущности.
	 *
	 * @var int
	 */
	public $author = 0;

	/**
	 * Заголовок сущности.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Текстовое содержимое сущности.
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * Дата создания сущности.
	 *
	 * @var string
	 */
	public $created_at = '';

	/**
	 * Слаг сущности (часть URL).
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Позиция для сортировки в интерфейсе.
	 *
	 * @var int
	 */
	public $menu_order = 0;

	/**
	 * ID вложения WordPress (featured image). 0 = нет картинки.
	 *
	 * @var int
	 */
	public $thumbnail_id = 0;

	/**
	 * Команда сущности.
	 *
	 * @var Team
	 */
	public $team;

	/**
	 * Аргументы — массив полей (алиасы SQL / свойства объекта).
	 *
	 * @param array $args Данные сущности.
	 */
	public function __construct( $args = array() ) {
		$this->id           = isset( $args['id'] ) ? (int) $args['id'] : 0;
		$this->parent       = isset( $args['parent'] ) ? (int) $args['parent'] : 0;
		$this->author       = isset( $args['author'] ) ? (int) $args['author'] : 0;
		$this->title        = isset( $args['title'] ) ? (string) $args['title'] : '';
		$this->content      = isset( $args['content'] ) ? (string) $args['content'] : '';
		$this->created_at   = isset( $args['created_at'] ) ? (string) $args['created_at'] : '';
		$this->slug         = isset( $args['slug'] ) ? (string) $args['slug'] : '';
		$this->menu_order   = isset( $args['menu_order'] ) ? (int) $args['menu_order'] : 0;
		$this->thumbnail_id = isset( $args['thumbnail_id'] ) ? (int) $args['thumbnail_id'] : 0;

		if ( isset( $args['team'] ) && $args['team'] instanceof Team ) {
			$this->team = $args['team'];
		} else {
			$this->team = new Team( isset( $args['team'] ) ? $args['team'] : '' );
		}
	}

	/**
	 * Тип сущности в нижнем регистре без пространства имён.
	 *
	 * @return string
	 */
	public function get_type() {
		$class = static::class;
		$pos   = strrpos( $class, '\\' );
		if ( false !== $pos ) {
			$class = substr( $class, $pos + 1 );
		}
		return strtolower( $class );
	}

	/**
	 * Регистрирует CPT сущности. Перекрывается потомками только при особых аргументах.
	 */
	public static function register() {
		\register_post_type(
			static::CPT,
			array(
				'labels'       => array(
					'name'          => static::CPT,
					'singular_name' => static::CPT,
				),
				'public'       => false,
				'show_ui'      => false,
				'show_in_rest' => false,
				'hierarchical' => false,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'author' ),
				'has_archive'  => false,
				'rewrite'      => false,
				'query_var'    => false,
			)
		);
	}

	/**
	 * Устанавливает картинку и сразу сохраняет сущность.
	 *
	 * @param int $attachment_id ID вложения WordPress.
	 */
	public function set_thumbnail( $attachment_id ) {
		$this->thumbnail_id = (int) $attachment_id;
		$this->save();
	}

	/**
	 * Возвращает ID вложения-картинки.
	 *
	 * @return int
	 */
	public function get_thumbnail_id() {
		return $this->thumbnail_id;
	}

	/**
	 * URL картинки указанного размера. Пустая строка, если картинки нет.
	 *
	 * @param string $size Размер изображения WordPress.
	 * @return string
	 */
	public function get_thumbnail_url( $size = 'full' ) {
		if ( empty( $this->thumbnail_id ) ) {
			return '';
		}
		$url = wp_get_attachment_image_url( $this->thumbnail_id, $size );
		return $url ? $url : '';
	}

	/**
	 * Ищет картинку по цепочке родительства и возвращает URL либо пустую строку.
	 *
	 * @param string $size Размер изображения WordPress.
	 * @return string
	 */
	public function get_thumbnail( $size = 'full' ) {
		$thumbnail_id = $this->find_thumbnail_id();
		if ( empty( $thumbnail_id ) ) {
			return '';
		}
		$url = wp_get_attachment_image_url( $thumbnail_id, $size );
		return $url ? $url : '';
	}

	/**
	 * Формирует аргументы для wp_insert_post() / wp_update_post().
	 *
	 * @return array
	 */
	protected function get_wp_args() {
		return array(
			'ID'           => $this->id,
			'menu_order'   => $this->menu_order,
			'post_author'  => $this->author,
			'post_content' => $this->content,
			'post_date'    => $this->created_at,
			'post_name'    => $this->slug,
			'post_parent'  => $this->parent,
			'post_status'  => 'publish',
			'post_title'   => $this->title,
			'meta_input'   => array(
				'_team'         => $this->team->get_members(),
				'_thumbnail_id' => $this->thumbnail_id,
			),
		);
	}

	/**
	 * Сохраняет сущность в БД и вызывает хуки create/update.
	 *
	 * @throws EntitySaveException Ошибка wp_insert_post / wp_update_post.
	 */
	public function save() {
		$args   = wp_slash( $this->get_wp_args() );
		$is_new = empty( $this->id );

		if ( $is_new ) {
			$result = wp_insert_post( $args, true );
		} else {
			$result = wp_update_post( $args, true );
		}

		if ( is_wp_error( $result ) || empty( $result ) ) {
			$message = is_wp_error( $result ) ? $result->get_error_message() : 'Failed to save entity';
			throw new EntitySaveException( $message );
		}

		if ( $is_new ) {
			$this->id = (int) $result;
		}

		$type  = $this->get_type();
		$event = $is_new ? 'create' : 'update';
		do_action( "cpm_core_entity_{$event}_{$type}", $this );
		do_action( "cpm_core_entity_{$event}", $this );

		Core_Manager::get_instance()->flush();
	}

	/**
	 * Рекурсивно удаляет дочерние сущности и текущую.
	 *
	 * @param bool $is_recursion Вложенный шаг: без хука delete и без flush().
	 */
	public function delete( $is_recursion = false ) {
		$manager = Core_Manager::get_instance();

		$child_entities = array();
		foreach ( array_keys( $manager->get_entity_types() ) as $type ) {
			$child_entities = array_merge(
				$child_entities,
				$manager->load_list( $type, array( 'parent' => $this->id ) )
			);
		}

		if ( ! $is_recursion ) {
			$type = $this->get_type();
			do_action( "cpm_core_entity_delete_{$type}", $this );
			do_action( 'cpm_core_entity_delete', $this );
		}

		foreach ( $child_entities as $child ) {
			$child->delete( true );
		}

		$this->delete_entity();

		if ( ! $is_recursion ) {
			$manager->flush();
		}
	}

	/**
	 * Удаляет запись сущности как post WordPress без корзины.
	 */
	protected function delete_entity() {
		wp_delete_post( $this->id, CPM_FORCE_DELETE );
	}

	/**
	 * ID картинки текущей сущности или ближайшего родителя по цепочке.
	 *
	 * @return int
	 */
	protected function find_thumbnail_id() {
		$entity = $this;
		while ( $entity ) {
			if ( ! empty( $entity->thumbnail_id ) ) {
				return $entity->thumbnail_id;
			}
			$entity = $entity->get_parent_entity();
		}
		return 0;
	}

	/**
	 * Родительская сущность для поиска картинки. В Entity цепочка не поднимается.
	 *
	 * @return Entity|null
	 */
	protected function get_parent_entity() {
		return null;
	}
}
