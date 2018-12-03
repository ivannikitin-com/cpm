<?php
/**
 * Вывод страницы статуса CPM.
 * Непосредствено подключается в метод CPM\Extensions\Manager::showStatusPage()
 *
 * @package CPM/Extensions
 * @version 2.0.0
 */

?>
<h1><?php _e( 'Статус CPM', 'cpm') ?></h1>

<section>
	<h2><?php _e( 'Загруженные расширения', 'cpm') ?></h2>
	<ol>
	<?php foreach ( $this->extensions as $extension ): ?>
		<li><?php echo $extension->getTitle() ?></li>
	<?php endforeach ?>		
	</ol>
</section>