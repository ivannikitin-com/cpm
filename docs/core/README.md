# Ядро CPM v3

Ядро CPM v3 — набор классов, обеспечивающих основную функциональность и хранение данных CPM в БД WordPress.

## Важное требование

Все данные CPM v3 хранятся в WordPress **исключительно штатными средствами WordPress**:

- Custom Post Type;
- мета-данные записи (`postmeta`).

Следует избегать создания и использования отдельных таблиц в БД WordPress, кроме явно оговорённых случаев (легаси-таблица `in_cpm_user_role` — только чтение для обратной совместимости).

## Файлы и классы ядра

Все файлы и классы ядра располагаются в `CPM_PLUGIN_DIR/classes/core`. Все классы ядра принадлежат пространству имён `\CPM\v3\Core\`.

## Карта классов ядра

| Класс | Файл | Спецификация |
| ----- | ---- | ------------ |
| `Core_Manager` | `core_manager.php` | `core-manager.md` |
| `IEntity` (интерфейс) | в файле `Entity` | `entity.md` |
| `Entity` (абстрактный) | `entity.php` | `entity.md` |
| `Project_Entity` (абстрактный) | `project_entity.php` | `entity.md` |
| `Project` | `project.php` | `project.md` |
| `Task_List` | `task_list.php` | `task-list.md` |
| `Task` | `task.php` | `task.md` |
| `Message` | `message.php` | `message.md` |
| `Milestone` | `milestone.php` | `milestone.md` |
| `Note` | `note.php` | `note.md` |
| `Attachment` | `attachment.php` | `attachment.md` |
| `Comment` | `comment.php` | `comment.md` |
| `Activity` | `activity.php` | `activity.md` |
| `User` | `user.php` | `user.md` |
| `Team` | `team.php` | `team.md` |
| `ACL` | `acl.php` | `acl.md` |
| `Base_Decorator` (абстрактный) | `base_decorator.php` | `base-decorator.md` |
| `ReadOnly_Decorator` | `read_only_decorator.php` | `read-only-decorator.md` |
| `Modify_Decorator` | `modify_decorator.php` | `modify-decorator.md` |
| `ModifyOwn_Decorator` | `modify_own_decorator.php` | `modify-own-decorator.md` |
| `Stuff_Decorator` | `stuff_decorator.php` | `stuff-decorator.md` |

## Иерархия классов

```
IEntity (interface)
 ├── Entity (abstract)          — базовые свойства и работа с БД
 │    ├── Project               — проект
 │    └── Project_Entity (abstract) — сущность проекта (project_id, get_project())
 │         ├── Task_List
 │         ├── Task
 │         ├── Message
 │         ├── Milestone
 │         ├── Note
 │         ├── Attachment
 │         ├── Comment          — хранение в таблице comments
 │         └── Activity         — хранение в таблице comments
 └── Base_Decorator (abstract, implements IEntity)
      ├── ReadOnly_Decorator
      ├── Modify_Decorator
      ├── ModifyOwn_Decorator
      └── Stuff_Decorator

Team ── User                     — участники сущности (экземпляры User)
ACL                             — статический класс ролей и прав
Core_Manager                    — менеджер ядра (фабрика, ACL, регистрация CPT)
```

## Функции ядра

- `Core_Manager` — реализация основных функций ядра (фабричные методы `load_list()` / `create()`, регистрация CPT, загрузка классов). См. `core-manager.md`.
- Сущности CPM (интерфейс `IEntity`, базовый `Entity`, абстрактный `Project_Entity` и конкретные классы).
- Управление пользователями на уровне сущностей: `User`, `Team`, `ACL`.
- Права и разрешения на уровне сущностей — декораторы (`Base_Decorator` и наследники).
- Справочник мета-полей: `мета-поля.md`.
- Таблица прав и маппинг «роль → декоратор»: `права-и-роли.md`.

## Сквозные документы ядра

| Документ | Содержание |
| -------- | ---------- |
| `мета-поля.md` | Нормативный справочник всех мета-полей сущностей |
| `права-и-роли.md` | Таблица прав по типам сущностей, маппинг роль→декоратор, формат `_team` (нормативный) |
| `семантика-ролей.md` | Смысл ролей manager / co_worker / client / administrator |
| `выборка-sql.md` | Выборка сущностей одним SQL-запросом |
| `чтение-участников-sql.md` | Чтение team (COALESCE старого и нового формата) |
| `хуки-события.md` | События CPM (хуки WordPress) |
| `контент-сущностей.md` | Представление контента сущностей |
| `кэширование.md` | Стратегия кэширования |
| `ошибки.md` | Обработка ошибок / иерархия исключений |
| `совместимость.md` | Режим обратной совместимости |
| `проверка-старых-данных.md` | Итоги проверки чтения старых данных по реальной БД |
| `открытые-вопросы.md` | Все открытые вопросы и принятые решения |
