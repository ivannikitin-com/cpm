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
	 * @param array $args Фильтры load_list: id, parent.
	 * @return array[]
	 */
	public static function query_rows( $args = array() ) {
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
