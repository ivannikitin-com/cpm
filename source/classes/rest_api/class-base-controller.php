<?php
/**
 * Class Base_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

use CPM\v3\Core\AccessDeniedException;
use CPM\v3\Core\BadUserException;
use CPM\v3\Core\Core_Manager;
use CPM\v3\Core\EntitySaveException;
use CPM\v3\Core\IEntity;
use CPM\v3\Plugin;

/**
 * Базовая логика REST-контроллеров CPM.
 *
 * Спецификация: docs/rest-api/архитектура.md, docs/rest-api/права-и-ошибки.md
 */
abstract class Base_Controller extends \WP_REST_Controller {

	/**
	 * Namespace маршрутов.
	 *
	 * @var string
	 */
	protected $namespace = 'cpm/v1';

	/**
	 * Возвращает менеджер ядра.
	 *
	 * @return Core_Manager
	 */
	protected function get_core() {
		return Core_Manager::get_instance();
	}

	/**
	 * Регистрирует маршруты контроллера.
	 *
	 * WP_REST_Controller не объявляет метод абстрактным, поэтому контракт
	 * обеспечивается соглашением: каждый конкретный контроллер обязан
	 * переопределить register_routes().
	 */

	/**
	 * Требуется ли аутентифицированный пользователь.
	 *
	 * Права CPM (что именно доступно) определяет ядро, а не этот callback.
	 *
	 * @return true|\WP_Error
	 */
	public function permission_check() {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'cpm_not_authenticated',
				'Требуется авторизация',
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Загружает одну сущность по ID (массив из 0/1 элемента).
	 *
	 * @param string $type Тип сущности ядра.
	 * @param int    $id   ID сущности.
	 * @return IEntity|null
	 */
	protected function get_entity( $type, $id ) {
		$list = $this->get_core()->load_list( $type, array( 'id' => (int) $id ) );
		return ! empty( $list ) ? $list[0] : null;
	}

	/**
	 * Превращает исключение ядра в WP_Error. Тонкий слой: логика — в ядре.
	 *
	 * @param \Throwable $e Исключение.
	 * @return \WP_Error
	 */
	protected function exception_to_error( $e ) {
		if ( $e instanceof AccessDeniedException ) {
			return new \WP_Error(
				'cpm_forbidden',
				'У вас нет прав на это действие',
				array( 'status' => 403 )
			);
		}
		if ( $e instanceof BadUserException ) {
			return new \WP_Error(
				'cpm_user_not_found',
				'Пользователь не найден',
				array( 'status' => 404 )
			);
		}
		if ( $e instanceof EntitySaveException ) {
			Plugin::get_instance()->log( $e->getMessage(), 'error' );
			return new \WP_Error(
				'cpm_save_failed',
				'Не удалось сохранить данные',
				array( 'status' => 500 )
			);
		}

		Plugin::get_instance()->log( $e->getMessage(), 'error' );
		return new \WP_Error(
			'cpm_error',
			'Внутренняя ошибка',
			array( 'status' => 500 )
		);
	}

	/**
	 * Обёртка вызова ядра: ловит исключения CPM и возвращает WP_Error.
	 *
	 * @param callable $callback Вызов ядра.
	 * @return mixed Результат либо WP_Error.
	 */
	protected function run_core( $callback ) {
		try {
			return $callback();
		} catch ( \Throwable $e ) {
			return $this->exception_to_error( $e );
		}
	}
}
