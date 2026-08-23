# Класс Task

Класс сущности «Задача в проекте» (`\CPM\v3\Core\Task`). Отражает отдельную задачу внутри списка задач (раздела) проекта CPM. Наследует `Project_Entity`.

## Статичные свойства и методы

| Член | Значение/Назначение |
| ---- | ------------------- |
| `CPT` | `cpm_task` |
| `SQL` | SQL-запрос выборки задач |
| `register()` | Регистрация CPT |

## Свойства

Дополнительно к наследуемым свойствам `Entity` и `Project_Entity`:

| Свойство | Тип | Описание |
| -------- | --- | -------- |
| `task_list_id` | int | ID списка задач (или родительской сущности — см. «Иерархия задач») |
| `task_list_title` | string | Название списка задач |
| `task_list_slug` | string | Слаг списка задач |
| `start` | string | Дата и время старта задачи, например `2019-11-18 00:00:00` |
| `due` | string | Дата и время срока задачи, например `2019-11-18 00:00:00` |
| `completed` | int | Признак завершения: `"1"` — завершено, `"0"` — не завершено |
| `completed_on` | string | Дата и время завершения задачи |
| `completed_by` | int | ID пользователя, завершившего задачу |
| `task_privacy` | string | Признак закрытой задачи «только для сотрудников»: `yes` — доступна только сотрудникам, `no` — всем участникам проекта |

## Иерархия задач

> Подтверждено реальной БД: в большинстве случаев задача принадлежит списку задач (`parent` = `cpm_task_list`). Однако в реальных данных встречаются задачи, `parent` которых — проект (`cpm_project`) или другая задача (вложенная подзадача, `cpm_task`). Распределение по реальной БД (16 284 задачи):
> - `cpm_task_list` — 16 279 (основной случай);
> - `cpm_project` — 3 (задача напрямую в проекте);
> - `cpm_task` — 2 (вложенные подзадачи).

Подзадачи в v3 **не используются** — это редкие остатки ранних попыток. Однако чтение старых данных должно их корректно обрабатывать, поэтому иерархия задач поддерживается на уровне чтения: свойство `parent` может указывать на список задач, проект или другую задачу.

### `get_project()` для задачи

Метод унаследован от `Project_Entity` и использует свойство `project_id`. Для корректной работы во всех случаях иерархии `project_id` должен вычисляться с учётом типа `parent`:

- `parent` = список задач → проект берётся из `project_id` списка задач (как в SQL-запросе ниже);
- `parent` = проект → проект — это сам `parent`;
- `parent` = задача (вложенная) → проект берётся через цепочку родителя родителя (задача → её `parent` → ... → проект).

В SQL-запросе основной случай (задача → список задач → проект) решается подзапросом `task_list`. Случаи `parent` = проект или = задача в основном запросе **не охватываются** и должны обрабатываться дополнительно при чтении единичной задачи, если они встречаются.

> **Открытый вопрос**: SQL-запрос для выборки **списка** задач строится из предположения, что `parent` задачи = список задач. Редкие случаи (`parent` = проект или = другая задача) в запросе списка **не покрыты** — такие задачи не будут корректно получать `project_id` при массовой выборке. Решение принято для чтения **единичной** задачи. Вопрос поведения при выборке списком (исключать, подтягивать через цепочку или не поддерживать вовсе) остаётся открытым. Подзадачи в v3 не используются, поэтому на практике это маловероятно. Зафиксировано в `открытые-вопросы.md`.

### Команда задачи (Team)

Свойство `team` хранит команду (участников) задачи. Это исполнители задачи, им отправляются уведомления и комментарии в задаче.

#### Обратная совместимость

В предыдущей версии CPM ID участников хранился в мета-полях задачи `_assigned`. В SQL-запросе это учтено и конвертировано в формат team: строка только с ID пользователей, без ролей, пример `"169":"","1":"","43":""`. (Роли нужны только в проекте.)

## SQL-запрос задач

Запрос хранится в статичном свойстве `SQL` и используется `Core_Manager`. Подробности чтения прав — `чтение-участников-sql.md`.

> **Прототип SQL.** В реальном коде — параметризованный запрос `$wpdb->prepare()`, имена таблиц `$wpdb->posts` / `$wpdb->postmeta`. Условие `( is_admin OR team LIKE ... )` формируется динамически. Подробнее: `выборка-sql.md`.

```sql
--
-- Получение задач в проекте CPM v3
--
SELECT
	-- Данные Entity
	p.ID AS id,
	MAX(p.post_parent) AS parent,
	MAX(p.post_author) AS author,
	MAX(p.post_title) AS title,
	MAX(p.post_content) AS content,
	MAX(p.post_date) AS created_at,
	MAX(p.post_name) AS slug,
	MAX(p.menu_order) AS menu_order,
	MAX(CASE WHEN pm.meta_key = '_thumbnail_id' THEN pm.meta_value ELSE NULL END) AS thumbnail_id,
	-- Данные Project_Entity
	MAX(task_list.project_id) AS project_id,
	MAX(task_list.project_title) AS project_title,
	MAX(task_list.project_slug) AS project_slug,
	-- Данные Task
	MAX(task_list.task_list_id) AS task_list_id,
	MAX(task_list.task_list_title) AS task_list_title,
	MAX(task_list.task_list_slug) AS task_list_slug,
	MAX(CASE WHEN pm.meta_key = '_start' THEN pm.meta_value ELSE NULL END) AS start,
	MAX(CASE WHEN pm.meta_key = '_due' THEN pm.meta_value ELSE NULL END) AS due,
	MAX(CASE WHEN pm.meta_key = '_completed' THEN pm.meta_value ELSE NULL END) AS completed,
	MAX(CASE WHEN pm.meta_key = '_completed_on' THEN pm.meta_value ELSE NULL END) AS completed_on,
	MAX(CASE WHEN pm.meta_key = '_completed_by' THEN pm.meta_value ELSE NULL END) AS completed_by,
	MAX(CASE WHEN pm.meta_key = '_task_privacy' THEN pm.meta_value ELSE NULL END) AS task_privacy,
	-- Реализация team с обратной совместимостью
	COALESCE(
		-- Новая схема участников задачи
		MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END),
		-- Старая схема участников задачи
		GROUP_CONCAT(CASE WHEN pm.meta_key = '_assigned' THEN CONCAT('"', pm.meta_value, '":""') ELSE NULL END)
	) AS team
FROM
	in_posts p
		INNER JOIN in_postmeta pm
			ON p.ID = pm.post_id
		INNER JOIN (
			-- Списки задач
			SELECT DISTINCT
				p_task_list.id AS task_list_id,
				p_task_list.post_title AS task_list_title,
				p_task_list.post_name AS task_list_slug,
				p_task_list.post_parent AS task_list_parent,
				project.id AS project_id,
				project.title AS project_title,
				project.slug AS project_slug
			FROM
				in_posts p_task_list
					INNER JOIN (
						-- Проекты текущего пользователя
						SELECT DISTINCT
							id,
							title,
							slug
						FROM (
							-- Проекты с участниками
							SELECT
								p.ID AS id,
								post_title AS title,
								post_name AS slug,
								COALESCE(
									-- Новая схема участников
									CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END,
									-- Старая схема участников
									(SELECT GROUP_CONCAT(CONCAT('"', user_id, '":', `role`)) FROM in_cpm_user_role r WHERE project_id = p.id GROUP BY project_id)
								) AS team
							FROM
								in_posts p
									INNER JOIN in_postmeta pm
										ON p.ID = pm.post_id
							WHERE
								post_type = 'cpm_project'
						) all_projects
						WHERE
							-- is_admin -- параметр запроса, true для администратора (фильтр по участнику не применяется)
							( is_admin OR team LIKE '%"277"%' ) -- '%"277"%' -- фильтр по участнику (ID текущего пользователя)
					) project ON
						p_task_list.post_parent = project.id
			WHERE
				post_type = 'cpm_task_list'
		) task_list
			ON p.post_parent = task_list.task_list_id
WHERE
	post_type = 'cpm_task'
GROUP BY
	id
HAVING
	TRUE
	-- Здесь могут быть любые дополнительные фильтры, например:
	-- AND project_id = 6583     -- Проект 6583
	-- AND project_slug = 'prestige-tm-ru'   -- Проект со слагом prestige-tm-ru
```
