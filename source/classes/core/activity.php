<?php
/**
 * Class Activity.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Запись журнала действий. Хранится как комментарий comment_type=cpm_activity.
 *
 * Спецификация: docs/core/activity.md
 */
class Activity extends Entity {

	const COMMENT_TYPE = 'cpm_activity';

	/**
	 * Подписи типов сущностей для человекочитаемого лога.
	 *
	 * @var string[]
	 */
	private static $type_labels = array(
		'project'    => 'Проект',
		'task_list'  => 'Список задач',
		'task'       => 'Задача',
		'message'    => 'Обсуждение',
		'milestone'  => 'Веха',
		'note'       => 'Заметка',
		'comment'    => 'Комментарий',
		'attachment' => 'Файл',
	);

	/**
	 * Хуки уже зарегистрированы в текущем запросе.
	 *
	 * @var bool
	 */
	private static $hooks_registered = false;

	/**
	 * Защита от циклов в get_project().
	 *
	 * @var bool
	 */
	private $resolving_project = false;

	/**
	 * Активность не регистрирует CPT (это не сущность WeDevs `activity`).
	 */
	public static function register() {}

	/**
	 * Подписка на хуки мутаций сущностей.
	 */
	public static function register_hooks() {
		if ( self::$hooks_registered ) {
			return;
		}
		self::$hooks_registered = true;
		add_action( 'cpm_core_entity_create', array( __CLASS__, 'on_create' ) );
		add_action( 'cpm_core_entity_update', array( __CLASS__, 'on_update' ) );
		add_action( 'cpm_core_entity_delete', array( __CLASS__, 'on_delete' ) );
	}

	/**
	 * @param IEntity $entity Созданная сущность.
	 */
	public static function on_create( $entity ) {
		self::record( 'create', $entity );
	}

	/**
	 * @param IEntity $entity Обновлённая сущность.
	 */
	public static function on_update( $entity ) {
		self::record( 'update', $entity );
	}

	/**
	 * @param IEntity $entity Удаляемая сущность.
	 */
	public static function on_delete( $entity ) {
		self::record( 'delete', $entity );
	}

	/**
	 * Человекочитаемый текст события (без шорткодов).
	 *
	 * @param string  $event  create|update|delete.
	 * @param IEntity $entity Сущность.
	 * @return string
	 */
	public static function format_message( $event, $entity ) {
		$user = wp_get_current_user();
		$name = ( $user && ! empty( $user->display_name ) ) ? $user->display_name : '';
		$type = $entity->get_type();
		$label = isset( self::$type_labels[ $type ] ) ? self::$type_labels[ $type ] : $type;
		$title = isset( $entity->title ) ? (string) $entity->title : '';
		if ( '' === $title && ! empty( $entity->content ) ) {
			$title = self::excerpt( (string) $entity->content );
		}

		$templates = array(
			'create' => '%s "%s" создан(а) пользователем %s',
			'update' => '%s "%s" изменён(а) пользователем %s',
			'delete' => '%s "%s" удалён(а) пользователем %s',
		);
		$template = isset( $templates[ $event ] ) ? $templates[ $event ] : $templates['update'];
		return sprintf( $template, $label, $title, $name );
	}

	/**
	 * Читает лог проекта. Старые записи со шорткодами отдаются как есть.
	 *
	 * @param int   $project_id ID проекта.
	 * @param array $args       Доп. аргументы get_comments().
	 * @return self[]
	 */
	public static function get_activity( $project_id, $args = array() ) {
		$defaults = array(
			'post_id' => (int) $project_id,
			'type'    => self::COMMENT_TYPE,
			'status'  => 'approve',
			'order'   => 'DESC',
			'number'  => 20,
		);
		$args     = apply_filters( 'cpm_activity_args', array_merge( $defaults, $args ), $project_id );
		$comments = get_comments( $args );
		if ( empty( $comments ) ) {
			return array();
		}

		$list = array();
		foreach ( $comments as $comment ) {
			$list[] = new self( self::comment_to_args( $comment ) );
		}
		return $list;
	}

	/**
	 * Выборка для Core_Manager::load_list().
	 *
	 * Поддерживает пагинацию и сортировку: per_page/page/offset/orderby/order.
	 *
	 * @param array $args Фильтры: parent/project_id, id, per_page, page, offset, orderby, order.
	 * @return array[]
	 */
	public static function query_rows( $args = array() ) {
		$query = array(
			'type'   => self::COMMENT_TYPE,
			'status' => 'approve',
			'order'  => 'DESC',
		);
		if ( isset( $args['parent'] ) ) {
			$query['post_id'] = (int) $args['parent'];
		} elseif ( isset( $args['project_id'] ) ) {
			$query['post_id'] = (int) $args['project_id'];
		}
		if ( isset( $args['id'] ) ) {
			$query['comment__in'] = array( (int) $args['id'] );
		}

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
	 * Число записей лога по фильтрам (без пагинации). Для Core_Manager::count_list().
	 *
	 * @param array $args Фильтры load_list: parent/project_id, id.
	 * @return int
	 */
	public static function query_count( $args = array() ) {
		$query = array(
			'type'   => self::COMMENT_TYPE,
			'status' => 'approve',
		);
		if ( isset( $args['parent'] ) ) {
			$query['post_id'] = (int) $args['parent'];
		} elseif ( isset( $args['project_id'] ) ) {
			$query['post_id'] = (int) $args['project_id'];
		}
		if ( isset( $args['id'] ) ) {
			$query['comment__in'] = array( (int) $args['id'] );
		}

		$query['count'] = true;
		$total          = get_comments( $query );
		return is_numeric( $total ) ? (int) $total : 0;
	}

	/**
	 * Сопоставление свойств Activity с полями WP_Comment_Query для сортировки.
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
	 * @param object $comment WP_Comment.
	 * @return array
	 */
	public static function comment_to_args( $comment ) {
		if ( is_array( $comment ) ) {
			$comment = (object) $comment;
		}
		return array(
			'id'         => isset( $comment->comment_ID ) ? (int) $comment->comment_ID : 0,
			'content'    => isset( $comment->comment_content ) ? (string) $comment->comment_content : '',
			'author'     => isset( $comment->user_id ) ? (int) $comment->user_id : 0,
			'created_at' => isset( $comment->comment_date ) ? (string) $comment->comment_date : '',
			'parent'     => isset( $comment->comment_post_ID ) ? (int) $comment->comment_post_ID : 0,
		);
	}

	/**
	 * Записывает строку лога к проекту (обычный текст, без шорткодов).
	 *
	 * @param int    $post_id ID проекта (comment_post_ID).
	 * @param string $message Текст.
	 * @return int ID комментария или 0.
	 */
	public function log( $post_id, $message ) {
		$commentdata = array(
			'comment_type'     => self::COMMENT_TYPE,
			'comment_content'  => $message,
			'comment_post_ID'  => (int) $post_id,
			'user_id'          => get_current_user_id(),
			'comment_approved' => 1,
		);
		$result = wp_insert_comment( $commentdata );
		return $result ? (int) $result : 0;
	}

	/**
	 * Проект, к которому относится запись лога.
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
	 * Активность только для чтения: сохранение через log().
	 *
	 * @throws AccessDeniedException
	 */
	public function save() {
		throw new AccessDeniedException( 'Activity log is read-only' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function delete_entity() {
		wp_delete_comment( $this->id, true );
	}

	/**
	 * @param string  $event  create|update|delete.
	 * @param IEntity $entity Сущность.
	 */
	private static function record( $event, $entity ) {
		if ( ! $entity instanceof IEntity ) {
			return;
		}
		if ( 'activity' === $entity->get_type() ) {
			return;
		}
		$project = $entity->get_project();
		if ( empty( $project ) ) {
			return;
		}
		$activity = new self();
		$activity->log( (int) $project->id, self::format_message( $event, $entity ) );
	}

	/**
	 * @param string $text Текст.
	 * @return string
	 */
	private static function excerpt( $text ) {
		$plain = wp_strip_all_tags( $text );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $plain, 0, 80 );
		}
		return substr( $plain, 0, 80 );
	}
}
