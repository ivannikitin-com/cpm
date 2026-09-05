<?php
/**
 * Class Attachment.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Файл вложения CPM. Штатный post_type=attachment, привязка к проекту через _project.
 *
 * Спецификация: docs/core/attachment.md
 */
class Attachment extends Project_Entity {

	const CPT = 'attachment';

	/**
	 * Шаблон SQL выборки вложений проекта.
	 *
	 * @var string
	 */
	public static $SQL = "
SELECT
	p.ID AS id,
	p.post_parent AS parent,
	p.post_author AS author,
	p.post_title AS title,
	p.post_content AS content,
	p.post_date AS created_at,
	p.post_name AS slug,
	p.menu_order,
	p.post_mime_type AS mime_type,
	project.id AS project_id,
	project.title AS project_title,
	project.slug AS project_slug,
	MAX(CASE WHEN pm.meta_key = '_parent' THEN pm.meta_value ELSE NULL END) AS attached_parent,
	MAX(CASE WHEN pm.meta_key = '_wp_attachment_metadata' THEN pm.meta_value ELSE NULL END) AS attachment_metadata
FROM
	{posts} p
		INNER JOIN {postmeta} pm
			ON p.ID = pm.post_id
		INNER JOIN (
			SELECT DISTINCT id, title, slug FROM (
				SELECT
					p.ID AS id,
					p.post_title AS title,
					p.post_name AS slug,
					COALESCE(
						MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END),
						(SELECT GROUP_CONCAT(CONCAT('\"', user_id, '\":', `role`)) FROM {cpm_user_role} r WHERE project_id = p.ID GROUP BY project_id)
					) AS team
				FROM {posts} p
					INNER JOIN {postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'cpm_project'
				GROUP BY p.ID
			) all_projects
			WHERE ( {is_admin} OR team LIKE {team_like} )
		) project
			ON project.id = (
				SELECT CAST(m.meta_value AS UNSIGNED)
				FROM {postmeta} m
				WHERE m.post_id = p.ID AND m.meta_key = '_project'
				LIMIT 1
			)
WHERE
	p.post_type = 'attachment'
GROUP BY
	p.ID
HAVING
	TRUE
";

	/**
	 * MIME-тип файла.
	 *
	 * @var string
	 */
	public $mime_type = '';

	/**
	 * Является ли вложение изображением.
	 *
	 * @var bool
	 */
	public $is_image = false;

	/**
	 * Ширина изображения, 0 если не применимо.
	 *
	 * @var int
	 */
	public $width = 0;

	/**
	 * Высота изображения, 0 если не применимо.
	 *
	 * @var int
	 */
	public $height = 0;

	/**
	 * Размер файла в байтах.
	 *
	 * @var int
	 */
	public $size = 0;

	/**
	 * @param array $args Данные вложения.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
		$this->mime_type = isset( $args['mime_type'] ) ? (string) $args['mime_type'] : '';
		$this->is_image  = isset( $args['is_image'] )
			? (bool) $args['is_image']
			: ( 0 === strpos( $this->mime_type, 'image/' ) );

		$meta = isset( $args['attachment_metadata'] ) ? $args['attachment_metadata'] : array();
		if ( is_string( $meta ) && '' !== $meta ) {
			$meta = maybe_unserialize( $meta );
		}
		if ( is_array( $meta ) ) {
			$this->width  = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
			$this->height = isset( $meta['height'] ) ? (int) $meta['height'] : 0;
			if ( isset( $meta['filesize'] ) ) {
				$this->size = (int) $meta['filesize'];
			}
		}
		if ( isset( $args['size'] ) ) {
			$this->size = (int) $args['size'];
		}
		if ( isset( $args['width'] ) ) {
			$this->width = (int) $args['width'];
		}
		if ( isset( $args['height'] ) ) {
			$this->height = (int) $args['height'];
		}
	}

	/**
	 * CPT attachment уже зарегистрирован WordPress.
	 */
	public static function register() {}

	/**
	 * URL содержимого через REST-прокси ядра.
	 *
	 * @param string|null $size Размер миниатюры для изображений.
	 * @return string
	 */
	public function get_url( $size = null ) {
		$url = rest_url( 'cpm/v1/attachment/' . (int) $this->id );
		if ( $size ) {
			$url = add_query_arg( 'size', $size, $url );
		}
		return $url;
	}

	/**
	 * URL миниатюры. Пустая строка, если не изображение.
	 *
	 * @return string
	 */
	public function get_thumbnail_url( $size = 'full' ) {
		if ( ! $this->is_image ) {
			return '';
		}
		if ( 'full' === $size ) {
			$size = 'thumbnail';
		}
		return $this->get_url( $size );
	}

	/**
	 * Идентификатор/URL иконки типа. Рендер — в UI.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon = wp_mime_type_icon( $this->mime_type ? $this->mime_type : $this->id );
		return $icon ? $icon : $this->mime_type;
	}

	/**
	 * URL скачивания через тот же REST-прокси.
	 *
	 * @return string
	 */
	public function get_download_url() {
		return add_query_arg( 'download', '1', $this->get_url() );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_wp_args() {
		$args = parent::get_wp_args();
		return array_merge(
			$args,
			array(
				'post_type'      => self::CPT,
				'post_mime_type' => $this->mime_type,
				'post_status'    => 'inherit',
				'meta_input'     => array_merge(
					$args['meta_input'],
					array(
						'_project' => $this->project_id,
						'_parent'  => $this->parent,
					)
				),
			)
		);
	}

	/**
	 * Удаляет запись вложения и физический файл.
	 */
	protected function delete_entity() {
		wp_delete_attachment( $this->id, true );
	}

	/**
	 * Приватная подпапка загрузок CPM относительно uploads.
	 *
	 * @var string
	 */
	const UPLOAD_SUBDIR = 'cpm';

	/**
	 * Максимальный размер загружаемого файла (байты), по умолчанию лимит WordPress.
	 *
	 * @return int
	 */
	public static function get_max_upload_size() {
		$default = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
		return (int) apply_filters( 'cpm_max_upload_size', $default );
	}

	/**
	 * Разрешённые MIME-типы загрузки (whitelist), настраивается фильтром.
	 *
	 * @return string[]
	 */
	public static function get_allowed_mime_types() {
		$defaults = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
			'pdf'          => 'application/pdf',
			'doc'          => 'application/msword',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'          => 'application/vnd.ms-excel',
			'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'ppt'          => 'application/vnd.ms-powerpoint',
			'pptx'         => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'txt'          => 'text/plain',
			'csv'          => 'text/csv',
			'zip'          => 'application/zip',
			'tar'          => 'application/x-tar',
			'gz'           => 'application/gzip',
			'7z'           => 'application/x-7z-compressed',
		);
		return (array) apply_filters( 'cpm_allowed_mime_types', $defaults );
	}

	/**
	 * Проверяет файл из массива $_FILES: передан, без ошибки, в лимите и по whitelist.
	 *
	 * Валидация чистая: не пишет файл и не создаёт запись. Возвращает WP_Error
	 * с кодом 400-семантики, либо true.
	 *
	 * @param array $file Элемент $_FILES: name, type, tmp_name, error, size.
	 * @return true|\WP_Error
	 */
	public static function validate_upload( $file ) {
		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) ) {
			return new \WP_Error( 'cpm_upload_no_file', 'Файл не передан.' );
		}
		if ( ! empty( $file['error'] ) ) {
			return new \WP_Error( 'cpm_upload_failed', 'Файл повреждён или не загружен.' );
		}
		if ( isset( $file['size'] ) && (int) $file['size'] > self::get_max_upload_size() ) {
			return new \WP_Error( 'cpm_upload_too_large', 'Файл превышает допустимый размер.' );
		}

		$name  = isset( $file['name'] ) ? $file['name'] : '';
		$mimes = self::get_allowed_mime_types();
		$type  = wp_check_filetype( $name, $mimes );
		if ( empty( $type['type'] ) ) {
			return new \WP_Error( 'cpm_upload_bad_type', 'Тип файла не поддерживается.' );
		}
		return true;
	}

	/**
	 * Сохраняет загруженный файл в приватную подпапку uploads/cpm и создаёт запись вложения.
	 *
	 * Механизм ядра: вызывается контроллером REST после получения multipart-файла.
	 * Права на создание вложения проверяет вызывающий слой (через декораторы/ACL).
	 *
	 * @param array $file       Элемент $_FILES.
	 * @param int   $project_id ID проекта (мета _project).
	 * @param int   $parent     ID сущности, к которой прикрепляется файл.
	 * @return int|\WP_Error ID вложения или ошибка.
	 */
	public static function create_from_upload( $file, $project_id = 0, $parent = 0 ) {
		$valid = self::validate_upload( $file );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$mimes = self::get_allowed_mime_types();
		$type  = wp_check_filetype( isset( $file['name'] ) ? $file['name'] : '', $mimes );
		$mime  = ! empty( $type['type'] ) ? $type['type'] : ( isset( $file['type'] ) ? $file['type'] : '' );

		// Перенаправляем загрузку в приватную подпапку uploads/cpm.
		$filter = function ( $uploads ) {
			$subdir = '/' . self::UPLOAD_SUBDIR;
			$uploads['subdir'] = $subdir;
			$uploads['path']   = $uploads['basedir'] . $subdir;
			$uploads['url']    = $uploads['baseurl'] . $subdir;
			return $uploads;
		};
		add_filter( 'upload_dir', $filter );
		$overrides = array(
			'test_form' => false,
			'mimes'     => $mimes,
		);
		$moved     = wp_handle_upload( $file, $overrides );
		remove_filter( 'upload_dir', $filter );
		if ( isset( $moved['error'] ) || is_wp_error( $moved ) ) {
			$message = is_wp_error( $moved ) ? $moved->get_error_message() : $moved['error'];
			return new \WP_Error( 'cpm_upload_save_failed', $message );
		}

		$name = isset( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';
		$name = pathinfo( $name, PATHINFO_FILENAME );
		$name = $name ? $name : 'attachment';

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $mime,
				'post_title'     => $name,
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_parent'    => (int) $parent,
			),
			$moved['file'],
			(int) $parent
		);
		if ( ! $attachment_id || is_wp_error( $attachment_id ) ) {
			return new \WP_Error( 'cpm_upload_save_failed', 'Не удалось сохранить вложение.' );
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $moved['file'] );
		wp_update_attachment_metadata( $attachment_id, $metadata );
		update_post_meta( $attachment_id, '_project', (int) $project_id );
		update_post_meta( $attachment_id, '_parent', (int) $parent );

		self::bind_to_comment( (int) $attachment_id, (int) $parent );

		return (int) $attachment_id;
	}

	/**
	 * Привязка вложения к комментарию через commentmeta._files, если родитель — комментарий.
	 *
	 * @param int $attachment_id ID вложения.
	 * @param int $parent        ID сущности-родителя (может быть комментарием).
	 */
	private static function bind_to_comment( $attachment_id, $parent ) {
		if ( ! $parent ) {
			return;
		}
		if ( ! function_exists( 'get_comment' ) ) {
			return;
		}
		$comment = get_comment( $parent );
		if ( ! $comment ) {
			return;
		}

		$files   = get_comment_meta( $parent, '_files', true );
		$files   = is_array( $files ) ? $files : array();
		$files[] = $attachment_id;
		$files   = array_values( array_unique( array_map( 'intval', $files ) ) );
		update_comment_meta( $parent, '_files', $files );
	}
}
