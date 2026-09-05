<?php
/**
 * Class Core_Manager.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

use CPM\v3\Base_Manager;

/**
 * Менеджер ядра CPM: загрузка классов ядра, регистрация CPT, фабрика сущностей.
 *
 * Спецификация: docs/core/core-manager.md, docs/core/права-и-роли.md
 */
class Core_Manager extends Base_Manager {

	/**
	 * Маппинг «тип сущности → класс сущности».
	 *
	 * @var string[]
	 */
	private static $entity_types = array(
		'project'    => 'Project',
		'task_list'  => 'Task_List',
		'task'       => 'Task',
		'message'    => 'Message',
		'milestone'  => 'Milestone',
		'note'       => 'Note',
		'comment'    => 'Comment',
		'attachment' => 'Attachment',
		'activity'   => 'Activity',
	);

	/**
	 * Нормативная таблица прав: тип → роль → { right, decorator }.
	 * Источник: docs/core/права-и-роли.md.
	 *
	 * Note в таблице не описан: участникам проекта — только чтение.
	 *
	 * @var array
	 */
	private static $rights = array(
		'project'    => array(
			ACL::MANAGER   => array( 'right' => 'modify', 'decorator' => 'Modify_Decorator' ),
			ACL::CO_WORKER => array( 'right' => 'read', 'decorator' => 'ReadOnly_Decorator' ),
			ACL::CLIENT    => array( 'right' => 'read', 'decorator' => 'ReadOnly_Decorator' ),
		),
		'task_list'  => array(
			ACL::MANAGER   => array( 'right' => 'read', 'decorator' => 'ReadOnly_Decorator' ),
			ACL::CO_WORKER => array( 'right' => 'read', 'decorator' => 'ReadOnly_Decorator' ),
			ACL::CLIENT    => array( 'right' => 'read', 'decorator' => 'ReadOnly_Decorator' ),
		),
		'task'       => array(
			ACL::MANAGER   => array( 'right' => 'modify', 'decorator' => 'Modify_Decorator' ),
			ACL::CO_WORKER => array( 'right' => 'modify_own', 'decorator' => 'ModifyOwn_Decorator' ),
			ACL::CLIENT    => array( 'right' => 'modify_own', 'decorator' => 'ModifyOwn_Decorator' ),
		),
		'comment'    => array(
			ACL::MANAGER   => array( 'right' => 'modify_own', 'decorator' => 'ModifyOwn_Decorator' ),
			ACL::CO_WORKER => array( 'right' => 'modify_own', 'decorator' => 'ModifyOwn_Decorator' ),
			ACL::CLIENT    => array( 'right' => 'modify_own', 'decorator' => 'ModifyOwn_Decorator' ),
		),
		'message'    => array(
			ACL::MANAGER   => array( 'right' => 'modify_own', 'decorator' => 'ModifyOwn_Decorator' ),
			ACL::CO_WORKER => array( 'right' => 'modify_own', 'decorator' => 'ModifyOwn_Decorator' ),
			ACL::CLIENT    => array( 'right' => 'modify_own', 'decorator' => 'ModifyOwn_Decorator' ),
		),
		'milestone'  => array(
			ACL::MANAGER   => array( 'right' => 'modify_own', 'decorator' => 'Stuff_Decorator' ),
			ACL::CO_WORKER => array( 'right' => 'modify_own', 'decorator' => 'Stuff_Decorator' ),
			ACL::CLIENT    => array( 'right' => 'no_access', 'decorator' => 'Stuff_Decorator' ),
		),
		'attachment' => array(
			ACL::MANAGER   => array( 'right' => 'modify_own', 'decorator' => 'Stuff_Decorator' ),
			ACL::CO_WORKER => array( 'right' => 'modify_own', 'decorator' => 'Stuff_Decorator' ),
			ACL::CLIENT    => array( 'right' => 'no_access', 'decorator' => 'Stuff_Decorator' ),
		),
		'activity'   => array(
			ACL::MANAGER   => array( 'right' => 'read', 'decorator' => 'ReadOnly_Decorator' ),
			ACL::CO_WORKER => array( 'right' => 'read', 'decorator' => 'ReadOnly_Decorator' ),
			ACL::CLIENT    => array( 'right' => 'no_access', 'decorator' => 'ReadOnly_Decorator' ),
		),
		'note'       => array(
			ACL::MANAGER   => array( 'right' => 'read', 'decorator' => 'ReadOnly_Decorator' ),
			ACL::CO_WORKER => array( 'right' => 'read', 'decorator' => 'ReadOnly_Decorator' ),
			ACL::CLIENT    => array( 'right' => 'read', 'decorator' => 'ReadOnly_Decorator' ),
		),
	);

	/**
	 * Разрешённые фильтры HAVING: ключ args → плейсхолдер prepare.
	 *
	 * @var string[]
	 */
	private static $having_filters = array(
		'id'           => '%d',
		'parent'       => '%d',
		'project_id'   => '%d',
		'slug'         => '%s',
		'task_list_id' => '%d',
	);

	/**
	 * Колонки сортировки списков по типам сущностей (белый список).
	 *
	 * Ключ — тип из get_type(). Значения — алиасы колонок, которые присутствуют
	 * в выборке соответствующего SQL-шаблона. Используется в apply_order() для
	 * защиты от SQL-инъекций через параметр orderby.
	 *
	 * @var string[][]
	 */
	private static $orderable = array(
		'project'    => array( 'id', 'parent', 'author', 'title', 'created_at', 'slug', 'menu_order', 'coordinator', 'active' ),
		'task_list'  => array( 'id', 'parent', 'author', 'title', 'created_at', 'slug', 'menu_order', 'project_id', 'milestone' ),
		'task'       => array(
			'id', 'parent', 'author', 'title', 'created_at', 'slug', 'menu_order', 'project_id',
			'task_list_id', 'start', 'due', 'completed', 'completed_on', 'completed_by', 'task_privacy',
		),
		'message'    => array( 'id', 'parent', 'author', 'title', 'created_at', 'slug', 'menu_order', 'project_id', 'milestone', 'message_privacy' ),
		'milestone'  => array( 'id', 'parent', 'author', 'title', 'created_at', 'slug', 'menu_order', 'project_id', 'due', 'completed' ),
		'note'       => array( 'id', 'parent', 'author', 'title', 'created_at', 'slug', 'menu_order', 'project_id', 'doc_type' ),
		'attachment' => array( 'id', 'parent', 'author', 'title', 'created_at', 'slug', 'menu_order', 'project_id', 'mime_type' ),
	);

	/**
	 * Экземпляр менеджера ядра (синглтон).
	 *
	 * @var Core_Manager
	 */
	private static $instance;

	/**
	 * Сервисный класс проверки доступа (ACL).
	 *
	 * @var string
	 */
	public $acl = '\CPM\v3\Core\ACL';

	/**
	 * Конструктор: загружает классы ядра и регистрирует CPT.
	 */
	public function __construct() {
		self::$instance = $this;
		parent::__construct();
		$this->require_core_classes();
		$this->register_cpt();
	}

	/**
	 * Возвращает экземпляр менеджера ядра.
	 *
	 * @return Core_Manager
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Фабричный метод выборки сущностей.
	 *
	 * Один параметризованный SQL на тип. Всегда возвращает массив.
	 *
	 * @param string $type Тип сущности.
	 * @param array  $args Аргументы фильтрации (id, parent, project_id, slug, …).
	 * @return IEntity[]
	 */
	public function load_list( $type, $args = array() ) {
		$class = $this->get_entity_class( $type );
		if ( ! $class ) {
			return array();
		}

		if ( is_callable( array( $class, 'query_rows' ) ) ) {
			$rows = call_user_func( array( $class, 'query_rows' ), $args );
		} else {
			global $wpdb;
			$sql  = $this->build_sql( $class, $args );
			$sql  = $this->apply_list_pagination( $type, $sql, $args );
			$rows = $wpdb->get_results( $sql, ARRAY_A );
		}
		if ( empty( $rows ) ) {
			return array();
		}

		$list = array();
		foreach ( $rows as $row ) {
			$entity  = new $class( $row );
			$wrapped = $this->decorate( $entity );
			if ( $wrapped ) {
				$list[] = $wrapped;
			}
		}
		return $list;
	}

	/**
	 * Общее число сущностей, соответствующих фильтрам (без пагинации).
	 *
	 * Нужно для заголовков пагинации REST (X-WP-Total / X-WP-TotalPages).
	 *
	 * @param string $type Тип сущности.
	 * @param array  $args Фильтры (page/per_page/orderby/order игнорируются).
	 * @return int
	 */
	public function count_list( $type, $args = array() ) {
		$class = $this->get_entity_class( $type );
		if ( ! $class ) {
			return 0;
		}

		if ( method_exists( $class, 'query_count' ) ) {
			return (int) call_user_func( array( $class, 'query_count' ), $args );
		}

		global $wpdb;
		$sql = $this->build_sql( $class, $args );
		$sql = 'SELECT COUNT(*) AS cpm_count FROM ( ' . $sql . ' ) AS cpm_rows';

		$total = $wpdb->get_var( $sql );
		return $total ? (int) $total : 0;
	}

	/**
	 * Фабричный метод создания сущности.
	 *
	 * @param string $type Тип сущности.
	 * @param array  $args Данные сущности.
	 * @return IEntity
	 * @throws AccessDeniedException Нет прав или неизвестный тип.
	 */
	public function create( $type, $args = array() ) {
		$class = $this->get_entity_class( $type );
		if ( ! $class ) {
			throw new AccessDeniedException( 'Unknown entity type' );
		}

		$entity = new $class( $args );
		if ( ! $this->can_create( $type, $entity ) ) {
			throw new AccessDeniedException( 'Cannot create this entity type' );
		}

		$entity->save();
		$this->flush();

		$wrapped = $this->decorate( $entity );
		return $wrapped ? $wrapped : $entity;
	}

	/**
	 * Оборачивает сущность декоратором по роли и таблице прав.
	 *
	 * @param IEntity $entity Сущность.
	 * @return IEntity|null null, если право no_access (исключить из выборки).
	 */
	public function decorate( $entity ) {
		$role = ACL::get_role( $entity );
		$type = $entity->get_type();

		if ( ACL::ADMINISTRATOR === $role ) {
			return new Modify_Decorator( $entity );
		}

		if ( ACL::NO_ROLE === $role ) {
			return new ReadOnly_Decorator( $entity );
		}

		$spec = $this->get_right_spec( $type, $role );
		if ( ! $spec || 'no_access' === $spec['right'] ) {
			return null;
		}

		$decorator = __NAMESPACE__ . '\\' . $spec['decorator'];
		return new $decorator( $entity );
	}

	/**
	 * Спецификация права для пары «тип × роль».
	 *
	 * @param string $type Тип сущности (get_type()).
	 * @param string $role Константа ACL.
	 * @return array|null { right, decorator }
	 */
	public function get_right_spec( $type, $role ) {
		if ( ! isset( self::$rights[ $type ][ $role ] ) ) {
			return null;
		}
		return self::$rights[ $type ][ $role ];
	}

	/**
	 * Сбрасывает кэши ядра и вызывает flush() у ACL.
	 */
	public function flush() {
		if ( method_exists( $this->acl, 'flush' ) ) {
			call_user_func( array( $this->acl, 'flush' ) );
		}
	}

	/**
	 * Маппинг «тип сущности → класс».
	 *
	 * @return string[]
	 */
	public function get_entity_types() {
		return self::$entity_types;
	}

	/**
	 * Сущность по ID записи WordPress (CPT ядра).
	 *
	 * @param int $post_id ID поста.
	 * @return IEntity|null
	 */
	public function load_by_post_id( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return null;
		}

		$post_type = get_post_type( $post_id );
		if ( empty( $post_type ) ) {
			return null;
		}

		foreach ( self::$entity_types as $type => $short ) {
			$class = __NAMESPACE__ . '\\' . $short;
			if ( ! class_exists( $class, false ) || ! defined( $class . '::CPT' ) ) {
				continue;
			}
			if ( $class::CPT !== $post_type ) {
				continue;
			}
			$list = $this->load_list( $type, array( 'id' => $post_id ) );
			return ! empty( $list ) ? $list[0] : null;
		}

		return null;
	}

	/**
	 * Полное имя класса сущности или null.
	 *
	 * @param string $type Тип из get_type().
	 * @return string|null
	 */
	public function get_entity_class( $type ) {
		if ( ! isset( self::$entity_types[ $type ] ) ) {
			return null;
		}
		$class = __NAMESPACE__ . '\\' . self::$entity_types[ $type ];
		return class_exists( $class, false ) ? $class : null;
	}

	/**
	 * Собирает параметризованный SQL из шаблона класса и фильтров $args.
	 *
	 * @param string $class Класс сущности.
	 * @param array  $args  Фильтры.
	 * @return string
	 */
	public function build_sql( $class, $args = array() ) {
		global $wpdb;

		$sql = $class::$SQL;
		$sql = str_replace(
			array( '{posts}', '{postmeta}', '{cpm_user_role}' ),
			array( $wpdb->posts, $wpdb->postmeta, $wpdb->prefix . 'cpm_user_role' ),
			$sql
		);

		$user_id  = (int) get_current_user_id();
		$is_admin = user_can( $user_id, 'manage_options' ) ? '1' : '0';
		$sql      = str_replace( '{is_admin}', $is_admin, $sql );

		// Значения подставляются через prepare() по одному, чтобы порядок
		// плейсхолдеров не зависел от того, WHERE {team_like} или HAVING раньше.
		if ( false !== strpos( $sql, '{team_like}' ) ) {
			$like = $wpdb->prepare( '%s', '%"' . $user_id . '"%' );
			$sql  = str_replace( '{team_like}', $like, $sql );
		}

		$having = array();
		foreach ( self::$having_filters as $key => $placeholder ) {
			if ( ! array_key_exists( $key, $args ) ) {
				continue;
			}
			$value    = ( '%d' === $placeholder ) ? (int) $args[ $key ] : (string) $args[ $key ];
			$having[] = $key . ' = ' . $wpdb->prepare( $placeholder, $value );
		}
		if ( $having ) {
			$sql = preg_replace(
				'/HAVING\s+TRUE/i',
				'HAVING TRUE AND ' . implode( ' AND ', $having ),
				$sql,
				1
			);
		}

		return $sql;
	}

	/**
	 * Применяет к SQL-запросу списка пагинацию и сортировку из $args.
	 *
	 * Распознаваемые ключи: page, per_page, offset, orderby, order.
	 * Колонка orderby — только из белого списка self::$orderable для типа.
	 *
	 * @param string $type Тип сущности.
	 * @param string $sql  Готовый SQL без LIMIT/OFFSET.
	 * @param array  $args Аргументы запроса.
	 * @return string
	 */
	private function apply_list_pagination( $type, $sql, $args ) {
		$has_page   = array_key_exists( 'per_page', $args ) || array_key_exists( 'page', $args ) || array_key_exists( 'offset', $args );
		$has_order  = ! empty( $args['orderby'] );
		if ( ! $has_page && ! $has_order ) {
			return $sql;
		}

		$orderby = isset( $args['orderby'] ) ? (string) $args['orderby'] : '';
		$order   = isset( $args['order'] ) ? strtolower( (string) $args['order'] ) : 'asc';
		$order   = in_array( $order, array( 'asc', 'desc' ), true ) ? $order : 'asc';

		$order_sql = '';
		if ( $has_order ) {
			$allowed = isset( self::$orderable[ $type ] ) ? self::$orderable[ $type ] : array();
			if ( in_array( $orderby, $allowed, true ) ) {
				$order_sql = ' ORDER BY `' . $orderby . '` ' . strtoupper( $order );
			}
		}

		if ( ! $has_page ) {
			return $order_sql ? 'SELECT * FROM ( ' . $sql . ' ) AS cpm_rows' . $order_sql : $sql;
		}

		$per_page = isset( $args['per_page'] ) ? max( 1, (int) $args['per_page'] ) : 0;
		$offset   = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;
		if ( $per_page && ! isset( $args['offset'] ) && isset( $args['page'] ) ) {
			$page   = max( 1, (int) $args['page'] );
			$offset = ( $page - 1 ) * $per_page;
		}

		$sql = 'SELECT * FROM ( ' . $sql . ' ) AS cpm_rows' . $order_sql;
		if ( $per_page ) {
			$sql .= ' LIMIT ' . (int) $per_page . ' OFFSET ' . (int) $offset;
		}
		return $sql;
	}

	/**
	 * Можно ли создать сущность данного типа текущим пользователем.
	 *
	 * @param string  $type   Тип.
	 * @param IEntity $entity Черновик сущности (для get_role / get_project).
	 * @return bool
	 */
	private function can_create( $type, $entity ) {
		if ( 'activity' === $type ) {
			return false;
		}
		$role = ACL::get_role( $entity );
		if ( ACL::ADMINISTRATOR === $role ) {
			return true;
		}
		$spec = $this->get_right_spec( $type, $role );
		if ( ! $spec ) {
			return false;
		}
		return in_array( $spec['right'], array( 'modify', 'modify_own' ), true );
	}

	/**
	 * Загружает все файлы классов ядра через require.
	 */
	private function require_core_classes() {
		$files = array(
			'cpm_exception.php',
			'bad_user_exception.php',
			'access_denied_exception.php',
			'entity_save_exception.php',
			'acl.php',
			'user.php',
			'team.php',
			'entity.php',
			'project_entity.php',
			'project.php',
			'task_list.php',
			'task.php',
			'message.php',
			'milestone.php',
			'note.php',
			'comment.php',
			'attachment.php',
			'activity.php',
			'base_decorator.php',
			'read_only_decorator.php',
			'modify_decorator.php',
			'modify_own_decorator.php',
			'stuff_decorator.php',
		);

		foreach ( $files as $file ) {
			$this->require_class( 'classes/core/' . $file );
		}
	}

	/**
	 * Регистрирует Custom Post Types ядра.
	 */
	private function register_cpt() {
		foreach ( self::$entity_types as $class ) {
			$class = __NAMESPACE__ . '\\' . $class;
			if ( ! class_exists( $class, false ) || ! method_exists( $class, 'register' ) ) {
				continue;
			}
			// attachment — штатный CPT WordPress, comment/activity — таблица comments.
			if ( defined( $class . '::CPT' ) && 'attachment' === $class::CPT ) {
				continue;
			}
			if ( ! defined( $class . '::CPT' ) ) {
				continue;
			}
			call_user_func( array( $class, 'register' ) );
		}
	}
}
