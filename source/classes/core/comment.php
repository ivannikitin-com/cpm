<?php
/**
 * Class Comment.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Комментарий к сущности CPM. Хранится в таблице comments, не CPT.
 *
 * Спецификация: docs/core/comment.md
 */
class Comment extends Entity {

	/**
	 * ID вложений из commentmeta._files.
	 *
	 * @var int[]
	 */
	public $files = array();

	/**
	 * Защита от циклов в get_project().
	 *
	 * @var bool
	 */
	private $resolving_project = false;

	/**
	 * @param array $args Данные комментария.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
		$this->files = $this->normalize_files( isset( $args['files'] ) ? $args['files'] : array() );
	}

	/**
	 * Комментарии не регистрируют CPT.
	 */
	public static function register() {}

	/**
	 * Выборка строк через API комментариев WordPress (не SQL CPT).
	 *
	 * Поддерживает пагинацию и сортировку: per_page/page/offset/orderby/order.
	 *
	 * @param array $args Фильтры load_list: id, parent, per_page, page, offset, orderby, order.
	 * @return array[]
	 */
	public static function query_rows( $args = array() ) {
		$query = self::comment_query_args( $args );
		$query = self::apply_query_pagination( $query, $args );

		$comments = get_comments( $query );
		if ( empty( $comments ) ) {
			return array();
		}

		$rows = array();
		foreach ( $comments as $comment ) {
			$rows[] = self::comment_to_args( $comment );
		}
		return $rows;
	}

	/**
	 * Число комментариев по фильтрам (без пагинации). Для Core_Manager::count_list().
	 *
	 * @param array $args Фильтры load_list: id, parent.
	 * @return int
	 */
	public static function query_count( $args = array() ) {
		$query = self::comment_query_args( $args );
		$query['count'] = true;
		$total = get_comments( $query );
		return is_numeric( $total ) ? (int) $total : 0;
	}

	/**
	 * Базовые аргументы WP_Comment_Query для обычных комментариев.
	 *
	 * @param array $args Фильтры load_list.
	 * @return array
	 */
	private static function comment_query_args( $args = array() ) {
		$query = array(
			'type'    => 'comment',
			'status'  => 'approve',
			'orderby' => 'comment_date_gmt',
			'order'   => 'ASC',
		);
		if ( isset( $args['parent'] ) ) {
			$query['post_id'] = (int) $args['parent'];
		}
		if ( isset( $args['id'] ) ) {
			$query['comment__in'] = array( (int) $args['id'] );
		}
		return $query;
	}

	/**
	 * Сопоставление свойств Comment с полями WP_Comment_Query для сортировки.
	 *
	 * @var string[]
	 */
	private static $order_map = array(
		'id'         => 'comment_ID',
		'parent'     => 'comment_post_ID',
		'author'     => 'user_id',
		'created_at' => 'comment_date',
		'content'    => 'comment_content',
	);

	/**
	 * Накладывает пагинацию/сортировку на аргументы WP_Comment_Query.
	 *
	 * @param array $query Аргументы WP_Comment_Query.
	 * @param array $args  Ключи load_list: per_page, page, offset, orderby, order.
	 * @return array
	 */
	private static function apply_query_pagination( $query, $args ) {
		if ( isset( $args['orderby'] ) && isset( self::$order_map[ $args['orderby'] ] ) ) {
			$query['orderby'] = self::$order_map[ $args['orderby'] ];
		}
		if ( isset( $args['order'] ) ) {
			$order = strtolower( (string) $args['order'] );
			if ( in_array( $order, array( 'asc', 'desc' ), true ) ) {
				$query['order'] = $order;
			}
		}

		$per_page = isset( $args['per_page'] ) ? max( 1, (int) $args['per_page'] ) : 0;
		if ( ! $per_page ) {
			return $query;
		}

		$query['number'] = $per_page;
		$offset          = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;
		if ( ! isset( $args['offset'] ) && isset( $args['page'] ) ) {
			$page   = max( 1, (int) $args['page'] );
			$offset = ( $page - 1 ) * $per_page;
		}
		if ( $offset ) {
			$query['offset'] = $offset;
		}
		return $query;
	}

	/**
	 * Преобразует объект комментария WP в аргументы конструктора.
	 *
	 * @param object $comment WP_Comment или stdClass.
	 * @return array
	 */
	public static function comment_to_args( $comment ) {
		if ( is_array( $comment ) ) {
			$comment = (object) $comment;
		}
		$id = isset( $comment->comment_ID ) ? (int) $comment->comment_ID : 0;
		$files = $id ? get_comment_meta( $id, '_files', true ) : array();
		return array(
			'id'         => $id,
			'content'    => isset( $comment->comment_content ) ? (string) $comment->comment_content : '',
			'author'     => isset( $comment->user_id ) ? (int) $comment->user_id : 0,
			'created_at' => isset( $comment->comment_date ) ? (string) $comment->comment_date : '',
			'parent'     => isset( $comment->comment_post_ID ) ? (int) $comment->comment_post_ID : 0,
			'files'      => $files,
		);
	}

	/**
	 * Проект родительской сущности (задача, сообщение, список, заметка, проект).
	 *
	 * @return Project|null
	 */
	public function get_project() {
		if ( $this->resolving_project ) {
			return null;
		}
		if ( empty( $this->parent ) ) {
			return null;
		}
		$this->resolving_project = true;
		try {
			$parent = Core_Manager::get_instance()->load_by_post_id( $this->parent );
			return $parent ? $parent->get_project() : null;
		} finally {
			$this->resolving_project = false;
		}
	}

	/**
	 * Аргументы для wp_insert_comment() / wp_update_comment().
	 *
	 * @return array
	 */
	protected function get_wp_args() {
		$args = array(
			'comment_post_ID'  => $this->parent,
			'comment_content'  => $this->content,
			'comment_date'     => $this->created_at,
			'user_id'          => $this->author,
			'comment_type'     => 'comment',
			'comment_approved' => 1,
		);
		if ( ! empty( $this->id ) ) {
			$args['comment_ID'] = $this->id;
		}
		return $args;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save() {
		$args   = wp_slash( $this->get_wp_args() );
		$is_new = empty( $this->id );

		if ( $is_new ) {
			$result = wp_insert_comment( $args );
		} else {
			$result = wp_update_comment( $args );
		}

		if ( is_wp_error( $result ) || false === $result || ( $is_new && empty( $result ) ) ) {
			$message = is_wp_error( $result ) ? $result->get_error_message() : 'Failed to save comment';
			throw new EntitySaveException( $message );
		}

		if ( $is_new ) {
			$this->id = (int) $result;
		}

		update_comment_meta( $this->id, '_files', $this->files );

		$type  = $this->get_type();
		$event = $is_new ? 'create' : 'update';
		do_action( "cpm_core_entity_{$event}_{$type}", $this );
		do_action( "cpm_core_entity_{$event}", $this );

		Core_Manager::get_instance()->flush();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function delete_entity() {
		wp_delete_comment( $this->id, true );
	}

	/**
	 * @param mixed $files Сырое значение _files.
	 * @return int[]
	 */
	private function normalize_files( $files ) {
		if ( is_string( $files ) && '' !== $files ) {
			$files = maybe_unserialize( $files );
		}
		if ( ! is_array( $files ) ) {
			return array();
		}
		return array_values( array_map( 'intval', $files ) );
	}
}
