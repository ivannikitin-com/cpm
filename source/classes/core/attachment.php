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
}
