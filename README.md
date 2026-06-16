# Enterprise RBAC CRM

Многотенантная CRM-система с гибридной RBAC-моделью (роли + прямые `grant`/`deny` permissions), полным аудит-логированием, feature-flags и invite-only регистрацией пользователей. Построена на Laravel 13, PHP 8.3+, Tailwind 4 и Pest 4.

## Содержание

- [Возможности](#возможности)
- [Стек](#стек)
- [Архитектура](#архитектура)
- [Установка с нуля](#установка-с-нуля)
- [Запуск](#запуск)
- [Роли и разрешения](#роли-и-разрешения)
- [Демо-пользователи](#демо-пользователи)
- [Сценарии для ручного тестирования](#сценарии-для-ручного-тестирования)
- [Структура проекта](#структура-проекта)
- [Запуск тестов](#запуск-тестов)
- [Полезные artisan-команды](#полезные-artisan-команды)

## Возможности

### Аутентификация и онбординг

- **Sign-in** по email/паролю с rate-limiting (`throttle:6,1`).
- **Invite-only регистрация**: tenant-admin приглашает пользователя с заранее выбранной ролью и опциональным department; токен инвайта живёт 7 дней (конфигурируется в `config/rbac.php`).
- **Авто-редирект после логина**: super-admin попадает в платформенную консоль, обычный пользователь — на дашборд своего тенанта.
- **Password reset by email** — `/forgot-password` → ссылка с одноразовым токеном (TTL 60 мин). Защита от user enumeration (одинаковый ответ для известных и неизвестных email), throttle 5/min, аудит `password_reset_requested` / `password_reset_completed`. После сброса все сессии пользователя инвалидируются.
- **Email verification (soft mode)** — `User implements MustVerifyEmail`. После логина баннер «Подтвердите email», кнопка _Resend_. Invite/seeders проставляют `email_verified_at = now()` сразу. Аудит `email_verification_sent` / `email_verified`.
- **Account lockout** — после `rbac.lockout.max_attempts=5` неверных попыток email-аккаунт блокируется на `rbac.lockout.duration_minutes=15` минут (`users.locked_until`). Успешный логин сбрасывает счётчик. Tenant Admin / Super Admin с правом `users.unlock` снимает блокировку из админ-UI. Аудит `account_locked` / `account_unlocked`.
- **Password policies** — настроены через `Password::defaults()`: production — `min 12 + mixedCase + numbers + symbols + uncompromised (HaveIBeenPwned)`; local/test — `min 8`. Применяется в reset, profile change и admin set-password.
- **Password history** — таблица `password_histories`, размер окна задаётся `config('rbac.password_history.size')` (дефолт 5, env `RBAC_PASSWORD_HISTORY_SIZE`; 0 = выключено). На любой смене пароля (self, reset, admin override, accept invitation) проверка отвергает совпадение с текущим хешем и с последними N. Для reset-flow проверка идёт до `Password::reset`, чтобы одноразовый токен не тратился впустую при отказе.
- **Self-service profile** (`/profile`) — изменить свой пароль (с проверкой текущего), переотправить email-verification, просмотреть активные сессии и завершить отдельную или все «прочие» сессии (`Auth::logoutOtherDevices`). Аудит `password_changed_by_self`, `session_terminated`.
- **Admin / Super-admin переопределение пароля** — на странице юзера в Admin есть форма _Set new password_. Super Admin может менять кому угодно; Tenant Admin — только пользователям своего тенанта **с ролью уровня ниже своей**, и никогда — super-admin. После смены все сессии цели уничтожаются. Аудит `password_changed_by_admin`.
- **Security headers** — middleware `SecurityHeaders` в web-стеке (`X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`; `Strict-Transport-Security` — только в production на HTTPS).
- **Force HTTPS** — `URL::forceScheme('https')` + `forceRootUrl` в production через `AppServiceProvider::boot()` (редирект 80→443 остаётся ответственностью proxy/web-сервера).
- Регистрация логинов/логаутов/неуспешных попыток входа в `audit_logs` через event-listeners (Laravel 11+ auto-discovers их по type-hint в `handle()`).

### Multi-tenancy

- **Path-based** маршруты `/t/{slug}/...` — каждый тенант изолирован своим slug.
- **Global scope** `TenantScope` автоматически фильтрует запросы доменных моделей по текущему `tenant_id`. Контекст хранится в `Illuminate\Support\Facades\Context` и user → tenant_id как fallback. Super-admin обходит scope.
- `BelongsToTenant` trait автоматически заполняет `tenant_id` при создании моделей.
- Tenant `is_active` toggle — приостановленный тенант запрещает доступ всем своим пользователям.

### RBAC

- **46 атомарных permission** в формате `module.action`, описанных как **PHP enum** `App\Enums\Permission` — type-safe, IDE-friendly, синхронизируются в БД сидером.
- **5 системных ролей** (`tenant-admin`, `manager`, `sales`, `auditor`, `viewer`) с уровнями (`level: 0-100`) для предотвращения privilege escalation.
- **Несколько ролей у одного пользователя** + **TTL** через `role_user.expires_at`.
- **JIT / time-bound elevated access** — tenant-admin выдаёт пользователю **временную роль на N часов** (`GrantTemporaryRole`), не трогая постоянные назначения; права исчезают автоматически по истечении срока.
- **Role inheritance** — `roles.parent_id` (self-FK): роль наследует permissions всей цепочки родителей (`viewer ← sales ← manager ← tenant-admin`). Резолвер обходит предков с защитой от циклов; `SetRoleParent` валидирует self/cross-tenant/cycle.
- **Wildcard permissions** — `module.*` (флаг `permissions.is_wildcard`) компактно выдаёт все права модуля; раскрываются в конкретные slug-и в резолвере, `deny` по-прежнему перебивает отдельный slug внутри wildcard.
- **Permission bundles** — `permission_groups` + UI «Apply a permission bundle» аддитивно домерживают готовый набор прав к роли.
- **Direct permissions per user** с типами `grant` / `deny` и TTL — `deny` всегда побеждает `grant` из роли (см. `ResolveUserPermissions`).
- **Custom roles per tenant** — tenant-admin создаёт свои роли (с `level < собственного`) и собирает их из permissions, которыми сам владеет; либо **клонирует** существующую роль (`CloneRole`, кнопка «Clone») в редактируемую копию на уровень ниже.
- **Permission preview / impact** — на странице роли live-индикатор «Unsaved: +N / −M», diff применённых изменений и список затронутых пользователей.
- **Usage tracking** — команда `php artisan rbac:usage` и каталог super-admin показывают по каждому праву число ролей/прямых выдач и количество отказов за окно (дефолт 30 дней), подсвечивая «мёртвые» permissions.
- **ABAC (атрибутные условия)** — таблица `permission_conditions` + JSON-DSL (`all`/`any`/`not`, листья `{attr, op, value}`, ссылки `$user.id`). `AbacGate` подключён в `TenantAuthorizer` **после** проверки права и может его сузить (все применимые условия должны выполняться). Аддитивно: право без условий работает как раньше. UI: `Admin → Access conditions` (для держателей `permissions.assign`).
- **ReBAC (права на экземпляр ресурса)** — таблица `resource_permissions` + `InstancePermissionGate`: можно выдать пользователю доступ к **конкретной** сделке, минуя роли. Подключён как fallback-стадия `TenantAuthorizer` (только расширяет доступ, не отменяет проверки активности/тенанта). UI: блок «Instance permissions (ReBAC)» на странице сделки.
- **Approval workflows** — крупные сделки (`amount >= rbac.approvals.deal_threshold`, дефолт $100k) уходят в многошаговое одобрение (`manager → tenant-admin`) вместо мгновенного закрытия. Инициатор не может одобрять свой запрос (separation of duties), reject возвращает сделку в `active`. UI: очередь `Approvals` с прогрессом шагов и бейджем в сайдбаре.
- **Separation of duties** — конфигурируемые `forbidden_role_pairs` в `config/rbac.php` (например, нельзя совмещать `auditor` + `tenant-admin`).
- **Гибридная авторизация**: `Gate::before` для super-admin → Policies возвращают `Response::allow()/deny('reason')` → каждая Policy делегирует в `TenantAuthorizer`, который проверяет уровни: super → user active → tenant active → cross-tenant → permission (роль/наследование/прямые/wildcard) **или** instance-grant (ReBAC) → ABAC-условия → контекстные правила Policy.
- **Кэш permissions** на user/tenant в `cache.store=database`, TTL настраивается, автоматическая инвалидация при изменениях ролей/прямых permissions.

### CRM

- **Companies, Contacts, Deals, Tasks, Activities** — полный CRUD c soft-delete.
- **Deal pipeline** со стадиями `lead → qualified → proposal → negotiation → won|lost`.
- **Контекстные политики**:
  - Редактировать deal можно только в статусе `draft` И только owner или сотрудник того же department.
  - `deals.approve` доступно только в рабочие часы (`config('rbac.business_hours')`, дефолт 9–18) и только в будни.
  - `tasks.complete` — только assignee или creator.
  - Пользователь не может удалить сам себя.
- **Department-aware** ограничения — deal может быть «закреплён» за подразделением.

### Audit log

- **Auditable trait** автоматически логирует CRUD по моделям (`Created`, `Updated`, `Deleted`, `Restored`) с diff (old_values/new_values).
- **Event listeners** записывают `Login`, `Logout`, `LoginFailed`.
- Записываются также `RolesAssigned`, `PermissionGranted`, `InvitationSent`, `DealApproved`, `TaskCompleted` и др.
- Контекст: tenant_id, user_id, ip_address, user_agent, url.
- Tenant-admin видит audit своего тенанта, super-admin — global audit с фильтрами по тенанту и по action.
- CSV-export аудита (за feature-flag `audit_export`).
- **Diff-просмотр и фильтры** — строки аудита раскрываются в side-by-side diff (`Field / Before / After` + metadata), фильтрация по пользователю и диапазону дат (`from`/`to`).
- **Структурированный канал** — каждая запись зеркалится в Monolog-канал `audit` (см. `config/audit.php` → `log_channel`), который можно перенаправить во внешний коллектор (Sentry/Datadog/OpenTelemetry/rsyslog) без изменения кода.
- **Per-tenant audit sinks** — таблица `audit_sinks` + UI `Admin → Audit sinks` (право `audit.manage`): tenant-admin настраивает webhook, на который queued-job `DeliverAuditLogToSink` шлёт подписанные (`X-Audit-Signature: sha256=hmac`) события в реальном времени; можно ограничить набором действий.
- **Retention + archive** — команда `php artisan audit:archive` выгружает записи старше окна (per-tenant `tenants.settings.audit_retention_days`, иначе `config('audit.retention.default_days')`, дефолт 90) в JSONL на диск и удаляет из БД; запланирована ежедневно в `routes/console.php`.
- **Critical-action confirmation** — деструктивные маршруты (suspend тенанта, удаление роли/sink, смена чужого пароля) закрыты middleware `password.confirm`.
- **Observability dashboard** (super-admin) — KPI, график объёма аудита за 14 дней, топ-действия и лента security-событий.
- **Live activity feed** — на дашборде тенанта виджет, опрашивающий JSON-эндпоинт `tenant.activity-feed` каждые 10с (право `audit.view`).

### Feature flags

- Таблицы `features` + `feature_tenant` (с `is_enabled` + опциональным `expires_at`).
- Middleware-alias `feature:slug` для гейтинга роутов.
- Super-admin включает/выключает фичи на странице тенанта.

## Стек

| Слой | Технология |
|------|-----------|
| Backend | PHP 8.3+, Laravel 13 |
| База данных | SQLite (dev) — все миграции совместимы с MySQL/Postgres |
| Кэш | `database` driver (можно заменить на redis) |
| Фронтенд | Blade + Tailwind CSS 4 (Vite) |
| Тесты | Pest 4 + LazilyRefreshDatabase |
| Форматтер | Laravel Pint |

## Архитектура

```
app/
├── Actions/                     # final readonly классы для бизнес-операций
│   ├── Audit/LogAuditEvent.php
│   ├── Authorization/
│   │   ├── ResolveUserPermissions.php   # ядро авторизации + кэш
│   │   ├── ForgetUserPermissionsCache.php
│   │   ├── AssignRolesToUser.php        # escalation prevention + constraint
│   │   ├── GrantDirectPermission.php
│   │   ├── SyncRolePermissions.php
│   │   └── ...
│   ├── Invitation/{InviteUser, AcceptInvitation}.php
│   └── Tenant/BootstrapTenant.php       # создаёт системные роли + General dep
├── Authorization/
│   ├── RoleDefinition.php               # value-object
│   ├── RoleRegistry.php                 # каталог системных ролей
│   ├── TenantAuthorizer.php             # центральная Response::allow|deny
│   └── Constraints/RoleAssignmentConstraint.php
├── Enums/                                # Permission, AuditAction, DealStage, …
├── Http/
│   ├── Controllers/{Admin,Auth,Crm,SuperAdmin}/
│   ├── Middleware/{ResolveTenant,CheckPermission,CheckFeature,EnsureSuperAdmin}
│   └── Requests/                         # FormRequest на каждый домен
├── Listeners/{RecordLogin,RecordLogout,RecordFailedLogin}.php
├── Models/
│   ├── Concerns/{BelongsToTenant,Auditable,HasRoles,HasPermissions}.php
│   └── Scopes/TenantScope.php
└── Policies/                             # тонкие, делегируют в TenantAuthorizer
config/rbac.php                           # cache_ttl, forbidden_role_pairs, business_hours
```

### Permission resolution pipeline

```
HTTP request
  └─ middleware: auth → tenant → permission (опц.) → feature (опц.)
      └─ Controller → $this->authorize('action', $model)
          └─ Gate::before  ──── super_admin? → ALLOW
          └─ Policy::action() → Response
              └─ TenantAuthorizer::allows()
                   ├─ user.is_active
                   ├─ tenant.is_active
                   ├─ resource.tenant_id == current.tenant_id
                   └─ ResolveUserPermissions::handle($user)
                        ├─ role permissions  (через role_user, активные)
                        ├─ + direct grants   (permission_user.type=grant)
                        └─ − direct denies   (permission_user.type=deny)
```

## Установка с нуля

### Предварительные требования

- PHP **8.3+**, расширения: `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `bcmath`.
- Composer 2.x.
- Node.js 20+ и npm.

### Шаги

```bash
# 1. Клонируем проект
git clone <repo-url> enterprise-rbac
cd enterprise-rbac

# 2. PHP-зависимости
composer install

# 3. JS-зависимости
npm install

# 4. .env (генерируется автоматически artisan-ом, но если нет — создайте из .env.example)
cp -n .env.example .env
php artisan key:generate

# 5. Подготовка БД (SQLite-файл)
mkdir -p database
touch database/database.sqlite

# 6. Миграции + сидеры (создаст 18 таблиц, 42 permissions, 5 ролей на тенант, демо-данные)
php artisan migrate:fresh --seed

# 7. Билд фронтенда для production
npm run build

# (опционально) — dev-режим Tailwind+HMR
# npm run dev
```

### Конфигурация

RBAC-настройки в `config/rbac.php`, настройки аудита/observability — в `config/audit.php`. Можно переопределить через `.env`:

```env
RBAC_CACHE_TTL=3600
RBAC_BUSINESS_HOURS_START=9
RBAC_BUSINESS_HOURS_END=18
RBAC_BUSINESS_HOURS_WEEKDAYS_ONLY=true
RBAC_INVITATION_TTL_DAYS=7
RBAC_USAGE_WINDOW_DAYS=30
RBAC_APPROVAL_DEAL_THRESHOLD=100000   # сделки >= порога уходят в multi-step approval

# Audit log & observability (config/audit.php)
AUDIT_LOG_CHANNEL=audit               # Monolog-канал для зеркалирования аудита (null — выключить)
AUDIT_RETENTION_DAYS=90               # окно хранения по умолчанию для audit:archive
AUDIT_ARCHIVE_DISK=local              # диск для JSONL-архивов
AUDIT_ARCHIVE_PATH=audit-archive      # путь на диске
AUDIT_SINKS_ENABLED=true              # включить per-tenant webhook-доставку
AUDIT_SINK_TIMEOUT=5                  # таймаут доставки, сек
AUDIT_SINK_TRIES=3                    # число попыток queued-job доставки
```

## Запуск

```bash
php artisan serve
```

Откройте `http://127.0.0.1:8000`. Невошедшие будут редиректнуты на `/login`. После входа:

- super-admin → `/super-admin/tenants`
- tenant user → `/t/{slug}` (например, `/t/acme`)

## Роли и разрешения

Создаются автоматически в каждом тенанте при `BootstrapTenant`. Уровень (`level`) определяет иерархию: пользователь не может назначить роль с уровнем `>= собственному`.

| Роль | Slug | Level | Описание |
|------|------|-------|----------|
| **Super Admin** | (флаг `is_super_admin=true`) | — | Платформенный администратор. Не привязан к тенанту, имеет доступ ко всему через `Gate::before`. Управляет тенантами, фичами, глобальным audit. |
| **Tenant Administrator** | `tenant-admin` | 90 | Полный доступ в пределах тенанта: пользователи, роли, права, departments, аудит, CRM, экспорт. |
| **Manager** | `manager` | 70 | CRM full access (CRUD без delete для companies/contacts/deals), approve deals, audit view. |
| **Sales Representative** | `sales` | 40 | Создаёт/редактирует CRM-записи, completes tasks; **не** удаляет, **не** approves. |
| **Auditor** | `auditor` | 30 | Read-only по CRM + полный доступ к audit + экспорт audit. По SoD не совмещается с `manager`/`tenant-admin`. |
| **Viewer** | `viewer` | 10 | Read-only по CRM. |

### Permission-матрица системных ролей

`✓` — есть, пустое — нет. Super-admin имеет всё.

| Permission | tenant-admin | manager | sales | auditor | viewer |
|------------|:--:|:--:|:--:|:--:|:--:|
| `users.view`      | ✓ | ✓ |   |   |   |
| `users.create`    | ✓ |   |   |   |   |
| `users.update`    | ✓ |   |   |   |   |
| `users.delete`    | ✓ |   |   |   |   |
| `users.invite`    | ✓ |   |   |   |   |
| `users.unlock`    | ✓ |   |   |   |   |
| `users.set-password` | ✓ |   |   |   |   |
| `roles.*`         | ✓ |   |   |   |   |
| `permissions.*`   | ✓ |   |   |   |   |
| `departments.view`| ✓ | ✓ |   |   |   |
| `departments.create/update/delete` | ✓ |   |   |   |   |
| `companies.view`  | ✓ | ✓ | ✓ | ✓ | ✓ |
| `companies.create`| ✓ | ✓ | ✓ |   |   |
| `companies.update`| ✓ | ✓ | ✓ |   |   |
| `companies.delete`| ✓ |   |   |   |   |
| `contacts.view`   | ✓ | ✓ | ✓ | ✓ | ✓ |
| `contacts.create` | ✓ | ✓ | ✓ |   |   |
| `contacts.update` | ✓ | ✓ | ✓ |   |   |
| `contacts.delete` | ✓ |   |   |   |   |
| `deals.view`      | ✓ | ✓ | ✓ | ✓ | ✓ |
| `deals.create`    | ✓ | ✓ | ✓ |   |   |
| `deals.update`    | ✓ | ✓ | ✓ |   |   |
| `deals.delete`    | ✓ |   |   |   |   |
| `deals.approve`   | ✓ | ✓ |   |   |   |
| `deals.export`    | ✓ |   |   |   |   |
| `tasks.view`      | ✓ | ✓ | ✓ | ✓ | ✓ |
| `tasks.create`    | ✓ | ✓ | ✓ |   |   |
| `tasks.update`    | ✓ | ✓ | ✓ |   |   |
| `tasks.complete`  | ✓ | ✓ | ✓ |   |   |
| `tasks.delete`    | ✓ |   |   |   |   |
| `activities.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `activities.create`| ✓ | ✓ | ✓ |   |   |
| `activities.update`| ✓ | ✓ | ✓ |   |   |
| `activities.delete`| ✓ |   |   |   |   |
| `audit.view`      | ✓ | ✓ |   | ✓ |   |
| `audit.export`    | ✓ |   |   | ✓ |   |
| `audit.manage`    | ✓ |   |   |   |   |
| `approvals.view`  | ✓ | ✓ |   |   |   |
| `features.view`   | ✓ |   |   |   |   |

### Что роль может — простыми словами

| Что | super | tenant-admin | manager | sales | auditor | viewer |
|-----|:--:|:--:|:--:|:--:|:--:|:--:|
| Создавать/удалять тенанты, переключать фичи | ✓ |   |   |   |   |   |
| Управлять пользователями, ролями, правами тенанта |   | ✓ |   |   |   |   |
| Приглашать новых пользователей |   | ✓ |   |   |   |   |
| **Сбросить блокировку аккаунта** (`Unlock`) | ✓ | ✓ |   |   |   |   |
| **Принудительно сменить пароль пользователю** | ✓ | ✓ (только нижестоящим в своём тенанте) |   |   |   |   |
| Создавать departments |   | ✓ |   |   |   |   |
| Создавать companies / contacts / deals |   | ✓ | ✓ | ✓ |   |   |
| Удалять CRM-записи |   | ✓ |   |   |   |   |
| Approve deal (рабочие часы, будни) |   | ✓ | ✓ |   |   |   |
| Просматривать audit log |   | ✓ | ✓ |   | ✓ |   |
| Экспортировать audit в CSV (feature flag) |   | ✓ |   |   | ✓ |   |
| Просматривать CRM |   | ✓ | ✓ | ✓ | ✓ | ✓ |
| Логиниться, видеть свой dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

### Дополнительные правила (контекст, не permission)

- `deals.update` срабатывает только если deal **в draft** И вы owner или из того же department.
- `deals.approve` дополнительно требует **рабочее время** (9–18, будни).
- `tasks.complete` — только assignee или creator.
- Нельзя удалить **самого себя** через `users.delete`.
- Нельзя назначить роль с `level >= собственному` (privilege escalation).
- Запрещённые пары ролей (separation of duties): `auditor`+`tenant-admin`, `auditor`+`manager`.

## Демо-пользователи

Пароль у всех — `password`. Создаются сидером `DemoTenantSeeder`.

### Платформа

| Email | Роль |
|-------|------|
| `super@enterprise-rbac.test` | **Super Admin** |

### Tenant `acme` (`/t/acme`)

| Email | Роль(и) | Особенность |
|-------|---------|-------------|
| `admin@acme.test` | tenant-admin | полные права в Acme |
| `manager@acme.test` | manager | sales-операции + approve + audit view |
| `sales@acme.test` | sales | стандартный продажник |
| `auditor@acme.test` | auditor | read-only + audit; нельзя совмещать с manager |
| `viewer@acme.test` | viewer | read-only |
| `multi@acme.test` | manager + sales | пример multiple-roles assignment |
| `temp@acme.test` | sales **(expires в 7 дней)** | пример TTL роли |

### Tenant `globex` (`/t/globex`)

| Email | Роль(и) | Особенность |
|-------|---------|-------------|
| `admin@globex.test` | tenant-admin | полный доступ |
| `manager@globex.test` | manager | |
| `sales@globex.test` | sales | |
| `auditor@globex.test` | auditor | |
| `viewer@globex.test` | viewer | |
| `granted@globex.test` | viewer **+ direct grant** `deals.update` | пример boost через permission_user |
| `denied@globex.test` | tenant-admin **+ direct deny** `deals.delete` | пример override: даже admin не может удалить deals |

## Сценарии для ручного тестирования

1. **Tenant isolation** — войдите как `admin@acme.test`, попробуйте открыть `/t/globex` → 403.
2. **Super-admin bypass** — войдите как `super@…`, перейдите в `/t/acme` → доступ есть, видны все данные.
3. **Permission escalation prevention** — войдите как `manager@acme.test`, на странице `Users → Mary Manager → Roles`, попробуйте отметить `tenant-admin` → flash-сообщение об ошибке.
4. **Direct grant** — `granted@globex.test` видит CRM (viewer) И может зайти в deal на редактирование (через прямой grant `deals.update`).
5. **Direct deny overrides role** — `denied@globex.test` — tenant-admin, но при попытке `Delete` на deal получает 403.
6. **Deal lifecycle** — `sales@acme.test` создаёт deal (draft) → редактирует → manager переключает на `proposal` → manager нажимает Approve (рабочее время) → deal стал `won/closed`. Try approve в субботу — 403.
7. **Invite flow** — `admin@acme.test` → Users → Invite (email + sales role). Откройте invitation link в инкогнито, примите → автологин в acme.
8. **Audit** — после любого действия выше посмотрите `Admin → Audit log` (фильтры по action), а super-admin видит глобальный лог с фильтром по тенанту.
9. **Feature flag** — у acme audit_export **включён**, у globex **выключен**. `admin@globex.test` нажимает Export CSV → flash error. Super-admin включает фичу в `/super-admin/tenants/globex` — экспорт работает.
10. **TTL role** — `temp@acme.test` имеет sales только 7 дней. Сделайте `Carbon::setTestNow(now()->addDays(10))` в tinker или вручную поменяйте `expires_at` в БД — пользователь потеряет permissions.
11. **Inactive user / tenant** — заглушите пользователя (`is_active=false`) или тенанта — все запросы вернут 403 с понятным reason из `TenantAuthorizer`.
12. **Password reset** — `/forgot-password` → введите `sales@acme.test`. Письмо отправится в `storage/logs/laravel.log` (mailer `log`). Найдите ссылку `/reset-password/{token}?email=...`, откройте — установите новый пароль ≥8 символов. Все сессии пользователя сброшены, в audit `password_reset_requested` + `password_reset_completed`.
13. **Account lockout** — 5 раз попробуйте войти `sales@acme.test` с неверным паролем → на 6-й попытке login заблокирован до `locked_until`. Войдите как `admin@acme.test`, откройте `Users → Sam Sales`, нажмите _Unlock / reset attempts_ — счётчик сбросился, audit `account_unlocked`.
14. **Email verification (soft)** — создайте через tinker `User::factory()->unverified()->create()` → войдите им: в layout появится amber-баннер. Нажмите _Resend link_ → письмо в логе. Откройте signed-URL — баннер исчезает.
15. **Self-service profile** — `/profile` (любой пользователь). Смените свой пароль (нужен текущий). Откройте сайт в другом браузере — это создаст вторую запись в `sessions`. На профиле нажмите _Terminate_ на чужой строке либо _Sign out other devices_ (требует ваш пароль) — те сессии исчезли, текущая жива.
16. **Admin override password** — войдите как `admin@acme.test`, откройте `Users → Sam Sales → Set new password`. Все сессии Сэма убиты, audit `password_changed_by_admin`. Та же страница для `Mary Manager` (manager) — Mary не видит секцию _Set new password_ (нет права `users.set-password`).
17. **Tenant Admin escalation guard** — `admin@acme.test` пытается сменить пароль другому tenant-admin того же тенанта — flash-ошибка «Cannot change the password of a user at or above your role level». То же для super-admin аккаунта.
18. **Security headers** — `curl -sI http://localhost:8000/login` → видны `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`. HSTS отсутствует (т.к. local + HTTP).
19. **Password history** — `admin@acme.test` смените пароль `sales@acme.test` на `new-strong-pw-1`. Попробуйте затем сменить ему пароль на тот же `new-strong-pw-1` → flash error «must differ from your last 5 passwords». Смените ещё 4 раза на разные пароли, потом верните `new-strong-pw-1` → теперь пройдёт (выпал из окна). Аналогично работает в `/profile` (нужен текущий пароль) и `/forgot-password` (proof: при отказе по history token не тратится — можно повторить тот же URL с другим паролем).
20. **Role inheritance** — `admin@acme.test` → `Roles → New role`, создайте роль уровня 5 без прав. На странице роли в карточке _Inheritance_ выберите родителя `Viewer` → сохраните. Назначьте роль пользователю — он получит все viewer-права. Попробуйте сделать родителем роль, которая уже её потомок → flash об ошибке цикла.
21. **Wildcard permission** — на странице любой кастомной роли отметьте чекбокс `deals.* (grant all)` → сохраните. Пользователь с этой ролью получит все 6 `deals.*` прав. Затем выдайте ему прямой `deny` на `deals.delete` (`Direct permissions`) — удаление сделок снова запрещено, остальные `deals.*` работают.
22. **Permission bundle** — на странице кастомной роли выберите бандл `CRM — read only` → _Apply bundle_. К текущим правам добавятся все `*.view`, ничего не удаляется.
23. **JIT temporary role** — `admin@acme.test` → `Users → Sam Sales`, блок _Grant temporary (JIT) role_: выдайте `Manager` на 1 час. Рядом с ролью появится бейдж «expires …». В tinker `Carbon::setTestNow(now()->addHours(2))` + `cache:clear` — Сэм теряет manager-права, sales остаётся.
24. **Permission diff / impact** — откройте роль, у которой есть пользователи: справа панель _Impact_ с их списком. Поменяйте чекбоксы — заголовок покажет «Unsaved: +N / −M». Сохраните → блок _Last change_ перечислит добавленные/удалённые slug-и.
25. **Clone role** — `Roles → Clone` напротив `Manager` → создаётся редактируемая копия уровнем ниже с теми же правами; вы попадёте в её редактор.
26. **Usage report** — `php artisan rbac:usage` в терминале (таблица по правам). В UI super-admin откройте `Permissions` — столбцы Roles / Direct users / Denied и подсветка невыданных прав. Сгенерируйте отказ (зайдите ролью без `deals.delete` и попробуйте удалить сделку), запустите `rbac:usage` ещё раз — счётчик Denied у `deals.delete` вырастет.
27. **ABAC-условие** — `admin@acme.test` → `Access conditions`. Демо-условие запрещает удалять `closed`-сделки. Добавьте своё: permission `deals.approve`, JSON `{"attr":"deal.owner_id","op":"=","value":"$user.id"}` — теперь approve сработает только для владельца сделки. Удалите условие, чтобы вернуть прежнее поведение.
28. **ReBAC instance-grant** — откройте сделку под `admin@acme.test`, блок _Instance permissions (ReBAC)_: выдайте `Vince Viewer` право `deals.update` на эту сделку. Зайдите как `viewer@acme.test` — редактировать можно только эту сделку, остальные — нет. (В демо у Vince уже есть один такой грант.)
29. **Approval workflow** — как `manager@acme.test` откройте дорогую сделку (`Enterprise platform rollout`, $250k) и нажмите _Approve & close_ — вместо закрытия сделка уходит в `Pending approval` (2 шага). Шаг 1 должен одобрить **другой** manager, шаг 2 — `admin@acme.test`. После второго одобрения сделка закрывается (`won/closed`); _Reject_ на любом шаге возвращает её в `active`. Пункт `Approvals` в сайдбаре показывает бейдж с числом ожидающих вашего решения.
30. **Audit diff + фильтры** — `admin@acme.test` → `Admin → Audit log`. Кликните строку с изменением (например `updated`) — раскроется side-by-side diff. Отфильтруйте по конкретному пользователю и диапазону дат `from/to`.
31. **Audit sink (webhook)** — `admin@acme.test` → `Admin → Audit sinks`, создайте sink с endpoint (например `https://webhook.site/...`) и секретом. Совершите любое действие — на endpoint придёт подписанный POST (заголовок `X-Audit-Signature`). Поле _Last delivered_ обновится; неуспех попадёт в _Last error_.
32. **Audit archive** — `php artisan audit:archive --dry-run` покажет, сколько записей попадёт под архивацию; `php artisan audit:archive` выгрузит старые записи в JSONL (`storage/app/audit-archive/{tenant}/...`) и удалит из БД, записав `audit_archived`. Окно можно переопределить через `tenants.settings.audit_retention_days` (`0` — выключить).
33. **Critical-action confirmation** — `admin@acme.test` попробуйте удалить роль (`Roles → Delete`) или сменить чужой пароль — система перенаправит на `Confirm your password`. После ввода пароля действие выполнится.
34. **Observability dashboard** — войдите как `super@…`, откройте `Observability` в сайдбаре: KPI-карточки, график объёма аудита за 14 дней, топ-действия и лента security-событий.
35. **Live activity feed** — на дашборде тенанта (пользователь с правом `audit.view`, например `admin@acme.test` или `auditor@acme.test`) виджет _Live activity_ опрашивает сервер каждые 10с; совершите действие в другой вкладке — новая запись подсветится в ленте.

## Структура проекта

```
.
├── app/                    # см. секцию «Архитектура»
├── bootstrap/
│   ├── app.php             # регистрация middleware-алиасов: tenant, permission, feature, super-admin
│   └── providers.php       # AppServiceProvider + AuthServiceProvider
├── config/
│   ├── rbac.php            # cache_ttl, forbidden_role_pairs, business_hours, invitation_ttl_days, approvals
│   └── audit.php           # log_channel, retention (archive), sinks (webhook delivery)
├── database/
│   ├── migrations/         # 18 RBAC + CRM миграций (00–17, упорядочены по зависимостям)
│   ├── factories/          # 13 factories
│   └── seeders/            # Module, Permission, Feature, SuperAdminUser, DemoTenant
├── resources/views/
│   ├── auth/{login,invitation}
│   ├── crm/{companies,contacts,deals,tasks,activities}
│   ├── admin/{users,roles,permissions,departments,audit}
│   ├── super-admin/{tenants,permissions,audit}
│   └── partials/{sidebar,topbar}
├── routes/web.php          # 74 роута
└── tests/
    ├── Unit/               # ResolveUserPermissions, TenantAuthorizer, TenantScope, RoleAssignmentConstraint
    └── Feature/            # auth, tenant isolation, RBAC, direct permissions, invitations, deal policy, audit, super-admin, features
```

## Запуск тестов

```bash
# все тесты
php artisan test --compact

# конкретный файл
php artisan test --compact tests/Feature/RbacAuthorizationTest.php

# фильтр по имени
php artisan test --compact --filter="allows tenant admin"
```

В проекте **56 тестов / 101 ассерт**. БД — `:memory:` SQLite, `LazilyRefreshDatabase`, прогон ~1.6 сек.

## Полезные artisan-команды

```bash
# полный ресет схемы + демо-данные
php artisan migrate:fresh --seed

# только применить новые миграции
php artisan migrate

# пересоздать каталог permissions из enum App\Enums\Permission (включая wildcard module.*)
php artisan db:seed --class=Database\\Seeders\\PermissionSeeder

# пересоздать глобальные permission-бандлы
php artisan db:seed --class=Database\\Seeders\\PermissionGroupSeeder

# отчёт по использованию permissions (роли/прямые выдачи/отказы); --unused — только невыданные
php artisan rbac:usage
php artisan rbac:usage --unused

# архивировать старые записи аудита в JSONL и удалить из БД (--tenant=slug, --dry-run)
php artisan audit:archive
php artisan audit:archive --dry-run

# список всех роутов
php artisan route:list

# очистить permission-кэш всем пользователям (после правки RoleRegistry)
php artisan cache:clear
```

## Форматирование

```bash
vendor/bin/pint --dirty           # только изменённое
vendor/bin/pint                   # весь проект
```

## Работа с SQLite с помощбю CLI

1. Открыть SQLite
```bash
sqlite3 database/database.sqlite
```

2. Показать таблицы
```bash
.tables
```

3. Посмотреть структуру таблицы
```bash
.schema users
```

4. Посмотреть данные
```bash
.mode table
SELECT * FROM users;
```

5. Выйти
```bash
.quit
```
