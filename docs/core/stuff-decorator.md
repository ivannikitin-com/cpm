# Класс Stuff_Decorator

Декоратор для сущностей, доступ к которым ограничен **только сотрудниками** (роли `MANAGER` и `CO_WORKER`). Реализует проверку по логике для сотрудников.

Наследуется от `Base_Decorator`.

## Поведение

- Доступ к сущности разрешён только пользователям с ролями `MANAGER` и `CO_WORKER`.
- `can_modify()` возвращает `true` для сотрудников при условии, что они являются авторами сущности (логика `modify_own`).
- Для роли `CLIENT` доступ к таким сущностям **запрещён** (в т.ч. и чтение) — сущности для сотрудников.
- `can_read()` (защищённый) — определяет право на чтение.

## Использование

Применяется для сущностей «для сотрудников», например:

- Веха (для `MANAGER`, `CO_WORKER` — `modify_own`; для `CLIENT` — `no_access`);
- Файл вложения `Attachment` (для `MANAGER`, `CO_WORKER` — `modify_own`; для `CLIENT` — `no_access`).

См. таблицу прав в `права-и-роли.md` и логику выбора декоратора в `core-manager.md`.

## Пример реализации

```php
protected function is_staff() {
	$role = ACL::get_role( $this->entity );
	return in_array( $role, array( ACL::MANAGER, ACL::CO_WORKER ) );
}

protected function can_read() {
	return $this->is_staff();
}

protected function can_modify() {
	return $this->is_staff()
		&& (int) $this->entity->author === (int) get_current_user_id();
}
```
