# Класс Project

Класс сущности «Проект» (`\CPM\v3\Core\Project`). Отражает проект в CPM. Наследует `Entity`.

## Статичные свойства и методы

| Член | Значение/Назначение |
| ---- | ------------------- |
| `CPT` | `cpm_project` |
| `SQL` | SQL-запрос выборки проектов (см. ниже) |
| `register()` | Регистрация CPT проекта |

## Свойства

Дополнительно к наследуемым свойствам `Entity`:

| Свойство | Тип | Описание |
| -------- | --- | -------- |
| `coordinator` | int | Координатор проекта (Product Owner), ID пользователя WordPress |
| `active` | string | Статус проекта: `yes` — активный проект, `no` — неактивный (архивный) |

## Методы

| Метод | Назначение |
| ----- | ---------- |
| `get_wp_args()` | Массив аргументов WordPress для сохранения (переопределён: добавляет CPT и мета-поля) |
| `set_coordinator($user_id)` | Устанавливает координатора проекта и сохраняет |
| `is_active()` | `true`, если проект активный |
| `archive()` | Помечает проект как архивный (`active = 'no'`) |
| `unarchive()` | Помечает проект как активный (`active = 'yes'`) |
| `save()` | Сохраняет проект |
| `delete_entity()` | Дополнительные действия при удалении проекта |

## Реализация `get_wp_args()`

Метод обязан быть переопределён: добавляет в массив аргументов CPT и новые мета-поля.

```php
protected function get_wp_args() {
	$args = parent::get_wp_args();
	return array_merge( $args, array(
		'post_type'  => static::CPT,
		'meta_input' => array_merge( $args['meta_input'], array(
			'_cpm_coordinator' => $this->coordinator,
			'_project_active'  => $this->active,
		) ),
	) );
}
```

## Координатор проекта и `set_coordinator()`

Координатор — пользователь, отвечающий за проект (фактически Product Owner). Он должен всегда оповещаться и быть участником всех задач, даже если не входит в Team объектов проекта.

**Однако** доступ к проекту и его дочерним объектам полностью определяется `Team` проекта. Если пользователь указан координатором, но отсутствует в Team проекта, доступа к проекту и его дочерним объектам у него нет.

`coordinator` — `int`, содержит ID пользователя WordPress. Метод `set_coordinator($user_id)` — сервисный: устанавливает свойство и одновременно сохраняет проект:

```php
public function set_coordinator( $user_id ) {
	$this->coordinator = $user_id;
	$this->save();
}
```

## Статус проекта: `active`, `archive()`, `unarchive()`

В старой версии CPM использовались два мета-поля:

- `_project_archive` — архивный проект. **В текущей версии НЕ ИСПОЛЬЗУЕТСЯ.**
- `_project_active` — активный проект.

Для упрощения обратной совместимости статус проекта отражается свойством `active` (соответствует мета-полю `_project_active`).

Методы `archive()` и `unarchive()` скрывают значения свойства от внешних вызовов:

```php
public function archive() {
	$this->active = 'no';
	$this->save();
}

public function unarchive() {
	$this->active = 'yes';
	$this->save();
}

public function is_active() {
	return 'yes' === $this->active;
}
```

## Удаление проекта и `delete_entity()`

При удалении проекта помимо удаления самой сущности нужно удалить записи об участниках из старой таблицы `in_cpm_user_role`, чтобы обеспечить переход на новую систему ведения проектов и очищать БД от старых нестандартных записей.

> **Кто может удалить проект**: `manager` может выполнять любые действия в проекте, **кроме удаления самого проекта**. Удаление проекта доступно только роли `ADMINISTRATOR`. Эта проверка реализуется на уровне `delete()` проекта, а не декоратором (декоратор `Modify_Decorator` разрешает полную модификацию). См. `права-и-роли.md`.

```php
protected function delete_entity() {
	// Удаление post стандартным способом
	parent::delete_entity();

	// Удаление записей из in_cpm_user_role SQL запросом
	// DELETE FROM in_cpm_user_role WHERE project_id = <$this->id>
}
```

Запрос удаления записей из `in_cpm_user_role`:

```sql
DELETE FROM in_cpm_user_role WHERE project_id=111
```

Должен выполняться подготовленным запросом с подстановкой `project_id` из `$this->id`.

## SQL-запрос списка проектов

Запрос хранится в статичном свойстве `SQL` и используется `Core_Manager` для получения списка проектов. Подробности чтения прав (поле `team`) — `чтение-участников-sql.md`.

> **Прототип SQL.** В реальном коде — параметризованный запрос `$wpdb->prepare()`, имена таблиц `$wpdb->posts` / `$wpdb->postmeta`. Условие `( is_admin OR team LIKE ... )` формируется динамически: `is_admin` — параметр (`true` для администратора, тогда фильтр по участнику не применяется), иначе в `team LIKE ...` подставляется ID текущего пользователя. Подробнее: `выборка-sql.md`.

```sql
SELECT
	ID AS id,
	post_parent AS parent,
	post_author AS author,
	post_title AS title,
	post_content AS content,
	post_date AS created_at,
	post_name AS slug,
	menu_order,
	MAX(CASE WHEN pm.meta_key = '_thumbnail_id' THEN pm.meta_value ELSE NULL END) AS thumbnail_id,
	COALESCE(
		-- Новая схема участников
		MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END),
		-- Старая схема участников
		(SELECT GROUP_CONCAT(CONCAT('"', user_id, '":', `role`)) FROM in_cpm_user_role r WHERE project_id = p.id GROUP BY project_id)
	) AS team,
	MAX(CASE WHEN pm.meta_key = '_cpm_coordinator' THEN pm.meta_value ELSE NULL END) AS coordinator,
	MAX(CASE WHEN pm.meta_key = '_project_active' THEN pm.meta_value ELSE NULL END) AS active
FROM
	in_posts p
		INNER JOIN in_postmeta pm
			ON p.ID = pm.post_id
WHERE
	post_type = 'cpm_project'
GROUP BY
	ID
HAVING
	TRUE
	-- Здесь могут быть любые дополнительные фильтры, например:
	-- AND id = 112              -- Проект 112
	-- AND team LIKE '%"48"%'    -- Все проекты пользователя 48
	-- AND slug = 'my-project'   -- Проект со слагом my-project
ORDER BY
	menu_order DESC,
	post_title ASC
```
