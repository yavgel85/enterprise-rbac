# Roadmap улучшений и новых фич

Документ описывает потенциальные улучшения текущей реализации Enterprise RBAC CRM. Предложения сгруппированы по тематическим разделам и приоритизированы. Для каждого пункта указаны: **зачем**, **что менять** (модели/код/миграции), **acceptance criteria** и **оценка сложности** (S/M/L) + **приоритет** (P0 — критично, P1 — важно, P2 — приятно иметь).

## Оглавление

0. [Легенда: приоритеты и сложность](#0-легенда-приоритеты-и-сложность)
1. [Безопасность и аутентификация](#1-безопасность-и-аутентификация)
2. [Авторизация и RBAC: расширения модели](#2-авторизация-и-rbac-расширения-модели)
3. [Audit log и observability](#3-audit-log-и-observability)
4. [Производительность и инфраструктура](#4-производительность-и-инфраструктура)
5. [CRM-функционал](#5-crm-функционал)
6. [Уведомления и интеграции](#6-уведомления-и-интеграции)
7. [API и расширяемость](#7-api-и-расширяемость)
8. [UX/UI](#8-uxui)
9. [Compliance, GDPR, ретеншн](#9-compliance-gdpr-ретеншн)
10. [Платформа SaaS и биллинг](#10-платформа-saas-и-биллинг)
11. [DevEx и качество кода](#11-devex-и-качество-кода)
12. [Сводная таблица приоритетов и roadmap по фазам](#12-сводная-таблица-приоритетов-и-roadmap-по-фазам)

---

## 0. Легенда: приоритеты и сложность

В каждом пункте задачи присутствует пара тегов вида `(Приоритет, Сложность)`, например `(P0, S)`.
Расшифровка ниже задана **относительно текущего проекта** (Laravel 13 + SQLite + Pest 4, одна команда из 1-2 разработчиков). Сложность — это не абстрактный человеко-час, а **усилия + риски** в этом конкретном кодовом базисе.

### 0.1 Приоритет

| Тег | Название | Что означает | Когда добавляется в спринт |
|-----|----------|-------------|----------------------------|
| **P0** | Критично / Must-have | Без этого нельзя релизить в production либо есть реальный риск (безопасность, потеря данных, юридические требования). Блокирует «годность» проекта. | Берётся в работу немедленно, до любых новых фич. |
| **P1** | Важно / Should-have | Создаёт значимую ценность для пользователя или команды разработки. Не блокирует релиз, но без этого продукт чувствуется «бедным». | Планируется в ближайшие 1-2 спринта (~1 месяц). |
| **P2** | Приятно иметь / Could-have | Усиливает продукт, но не обязательно. Часто это «зрелость» фич или nice-to-have-интеграции. Может откладываться или вообще не реализовываться без бизнес-сигнала. | По мере появления свободной ёмкости команды или конкретного запроса. |

Если возникнет необходимость в категории "не делаем сейчас, но не отказываемся" — заводится отдельный `P3 / Won't have now`.

### 0.2 Сложность

Сложность учитывает **четыре фактора одновременно** (нельзя их разделять):

1. **Объём кода** — сколько файлов/таблиц/строк нужно создать или изменить.
2. **Связность** — сколько существующих модулей задеваем (миграции, политики, кэш, UI, тесты).
3. **Риск регрессии** — насколько легко сломать уже работающее (особенно RBAC-пайплайн и multi-tenancy).
4. **Внешние зависимости** — новые пакеты, инфраструктура (Redis, S3, OAuth-провайдеры), сторонние API.

| Тег | Название | Описание и характеристики | Типичная оценка времени для **одного** разработчика | Признаки попадания в категорию |
|-----|----------|---------------------------|-----------------------------------------------------|-------------------------------|
| **S** | Small / Малая | Простое локальное изменение: 1-3 файла или 1 новая таблица; ноль или один новый пакет; не задевает RBAC-ядро; покрывается 2-5 тестами; легко откатить. | **0.5-2 дня** (до 16 часов разработки). | • Одна миграция или одна модель.<br>• Один новый middleware/listener.<br>• UI-изменение в пределах одного экрана.<br>• Полная конфигурация (env + 1 файл).<br>• Тесты пишутся быстрее, чем меняется код. |
| **M** | Medium / Средняя | Кросс-модульная фича: 5-15 файлов; 1-3 миграции; затрагивает контроллеры + actions + политики + view + тесты; возможны новые пакеты с собственной конфигурацией; могут понадобиться очередь/cron. | **2-7 дней** (16-56 часов разработки). | • Новый workflow от запроса до записи в БД.<br>• Background job + retry-логика.<br>• Серия из 3-5 новых тестов (unit + feature).<br>• Изменение схемы кэша.<br>• Новое UI-меню/раздел + 2-4 экрана. |
| **L** | Large / Большая | Архитектурная задача или новая подсистема: 15+ файлов; >3 миграций; затрагивает RBAC/multi-tenancy/политики/слой авторизации одновременно; внешние интеграции; миграция данных; разработка вместе с DevOps; требуется ADR (architectural decision record). | **1-3 недели и более** (≥56 часов разработки, нередко работа более одного разработчика и/или нескольких спринтов). | • Новая подсистема (биллинг, SSO, custom fields/EAV, GraphQL).<br>• Изменение фундамента (DDD-разделение, role inheritance, ABAC-слой).<br>• Внешняя система (Stripe webhooks, SAML IdP, S3-архивация аудита).<br>• Требуется параллельная работа frontend + backend + ops.<br>• Несколько феьз с feature flag и поэтапной выкаткой. |

> **Важно.** Сложность ≠ ценность. Иногда `S` пункт (например `P0/S` — Force HTTPS) куда важнее, чем красивый `L` (например `P2/L` — GraphQL). При планировании всегда смотрим пару `(приоритет, сложность)` вместе.

### 0.3 Дополнительные обозначения

- **Фаза 1-4** — рекомендуемый порядок реализации (см. [12. Сводная таблица и roadmap по фазам](#12-сводная-таблица-приоритетов-и-roadmap-по-фазам)).
- **Feature flag** упоминается там, где предполагается раскладка функции через таблицу `features` + `feature_tenant` (привязка к плану в SaaS-модели).
- **ADR (Architectural Decision Record)** — короткий документ в `docs/adr/NNNN-title.md`, в котором фиксируется выбор архитектуры. Для `L`-задач ADR обязателен **до** начала имплементации.

### 0.4 Как использовать оценки при планировании

1. Сортируем по приоритету `P0 → P1 → P2`.
2. Внутри приоритета берём сначала `S`-задачи (быстрый возврат инвестиций), затем `M`, в последнюю очередь `L`.
3. `L`-задачи разбиваются на несколько `M`/`S` под-задач со своими acceptance-критериями; в roadmap они занимают отдельный спринт целиком.
4. На каждый спринт примерно: 70% времени — фичи (`S`+`M`), 20% — техдолг/observability, 10% — резерв на инциденты.

---

## 1. Безопасность и аутентификация

### 1.1 Password reset flow ✅ (P0, S) — _Сделано_

**Проблема.** Сейчас сбросить пароль невозможно — ни из UI, ни из CLI. Любая забывчивость заблокирует пользователя навсегда.

**Что менять.**
- Включить `auth.passwords.users` (Laravel из коробки).
- Контроллер `Auth\PasswordResetController` (`requestForm`, `sendLink`, `resetForm`, `reset`).
- Очередить отправку через Mail и `ShouldQueue`.
- Логировать `password_reset_requested` и `password_changed` в `AuditAction`.

**Acceptance.** Любой пользователь может запросить ссылку на свой email; ссылка работает 60 мин; одноразовая; не работает для `is_active=false`; событие в audit log.

> **Реализовано.** `Auth\PasswordResetController` (4 экшна), throttle 5/min на запросы, generic-ответ против user enumeration, аудит `password_reset_requested`/`password_reset_completed`, на странице логина — ссылка _Forgot password?_, при reset инвалидируются все сессии пользователя и сбрасывается lockout. Pest: `tests/Feature/PasswordResetTest.php`.

### 1.2 Email verification ✅ (P1, S) — _Сделано_

**Проблема.** Поле `email_verified_at` есть, но flow не реализован. Инвайт сразу выставляет verified.

**Что менять.** Реализовать `MustVerifyEmail` на `User`, плюс ручной `resend` для tenant-admin, плюс middleware-блокировка чувствительных действий (например, `users.invite`) пока email не подтверждён.

> **Реализовано (soft mode).** `User implements MustVerifyEmail`, маршруты `verification.notice/verify/send` (signed + throttle), баннер «подтвердите email» в `layouts/app`, страница профиля с кнопкой _Resend verification_. Login доступен и без верификации (soft-режим), но это переключаемо ужесточением middleware. Аудит `email_verification_sent`, `email_verified`. Pest: `tests/Feature/EmailVerificationTest.php`.

### 1.3 Two-Factor Authentication (P1, M)

**Зачем.** Корпоративные клиенты требуют TOTP/WebAuthn.

**Что менять.**
- Таблица `two_factor_secrets(user_id, secret_encrypted, confirmed_at, recovery_codes_encrypted)`.
- Зависимость `pragmarx/google2fa-laravel` или собственная реализация.
- Middleware `2fa` (после `auth`), вычислять `session('2fa.passed_at')`.
- UI: страница `Setup 2FA` с QR-кодом + 8 recovery-кодов.
- Tenant-admin может **требовать** 2FA для своих пользователей: `tenants.settings -> require_2fa: bool`.
- Super-admin всегда обязан включать 2FA.

**Acceptance.** Юзер с включённым 2FA после `auth/login` отправляется на `/2fa/challenge` и не пускается дальше без OTP; recovery-код одноразовый.

### 1.4 WebAuthn / Passkeys (P2, L)

Используя `bipassion/webauthn` или `web-auth/webauthn-lib`. Привязка нескольких устройств. Хранить `public_key`, `credential_id`, `sign_count`. UX: `Sign in with passkey`.

### 1.5 Account lockout ✅ (P1, S) — _Сделано_

После `N` failed login (конфиг `auth.lockout.max=5, decay=15m`) блокировать `User.is_active=false` с автоматическим разблоком через X минут, либо требованием password-reset. Уже есть `throttle:6,1` для IP — добавить **per-account**.

> **Реализовано.** Новые колонки `users.failed_login_attempts`, `users.locked_until` (миграция `add_lockout_fields_to_users_table`). Конфиг `rbac.lockout.max_attempts=5`, `rbac.lockout.duration_minutes=15`. Listener `RecordFailedLogin` инкрементирует счётчик и ставит lock; успешный login сбрасывает оба поля; `LoginController` отказывает в авторизации пока `locked_until` в будущем. Новый permission `users.unlock` + действие `UnlockUserAccount` + кнопка «Unlock / reset attempts» на `admin/users/show`. Аудит `account_locked`, `account_unlocked`. Pest: `tests/Feature/AccountLockoutTest.php`.

### 1.6 SSO: SAML 2.0 / OIDC (P1, L)

**Зачем.** Корпоративный onboarding (Okta, Azure AD, Google Workspace).

**Что менять.**
- Установить `socialiteproviders/saml` или `socialiteproviders/microsoft` + `laravel/socialite`.
- Per-tenant `auth_providers(tenant_id, type, config json, is_active)` — каждый тенант настраивает свой IdP.
- На странице логина: если email домена принадлежит тенанту с SSO — редиректить на IdP вместо локального login.
- JIT-provisioning: при первом логине через SSO создаём `User` с дефолтной ролью (`auth_providers.default_role_id`).

### 1.7 Session management UI ✅ (P2, S) — _Сделано_

`personal_access_tokens` / `sessions`-таблица: показать активные сессии пользователю с возможностью `Sign out other devices`. Уже есть `last_login_at`/`last_login_ip` — расширить до журнала.

> **Реализовано.** Страница `/profile`: смена своего пароля (с проверкой текущего), resend email-верификации, список активных сессий из таблицы `sessions` с пометкой текущего устройства, кнопка _Terminate_ для отдельной сессии и _Sign out other devices_ (требует пароль). Действие `ChangeOwnPassword` использует `Auth::logoutOtherDevices`, сохраняя текущую сессию. Аудит `password_changed_by_self`, `session_terminated`. Sidebar пополнен ссылкой _My profile_. Pest: `tests/Feature/ProfileTest.php`.

### 1.8 Security headers middleware ✅ (P1, S) — _Сделано_

Добавить middleware `SecurityHeaders` (`Strict-Transport-Security`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Content-Security-Policy` со скриптовым `nonce`). Подключить в `bootstrap/app.php` глобально.

> **Реализовано.** `App\Http\Middleware\SecurityHeaders`, добавлен в web-группу через `bootstrap/app.php`. Шлёт `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, расширенный `Permissions-Policy`. `Strict-Transport-Security` устанавливается только в production-окружении на HTTPS-запросах. CSP осознанно отложен — требует аудита inline-стилей Tailwind/Vite. Pest: `tests/Feature/SecurityHeadersTest.php`.

### 1.9 Force HTTPS in production ✅ (P0, S) — _Сделано_

`URL::forceHttps()` в `AppServiceProvider::boot()` если `app()->environment('production')`. Добавить middleware-редирект 301 c http на https.

> **Реализовано.** В `AppServiceProvider::boot()` при `App::isProduction()` вызываются `URL::forceScheme('https')` и `URL::forceRootUrl(config('app.url'))`. 301-редиректы с 80 на 443 оставлены на стороне reverse-proxy/веб-сервера (см. примечание в коммите).

### 1.10 Password policies ✅ (P2, S) — _Сделано_

Конфиг `auth.password_policy` (min length, complexity, history of 5, max age 90 days). Кастомное правило `Password::default()` + таблица `password_history(user_id, hash, created_at)`.

> **Реализовано.**
>
> - **Min length + complexity.** `Password::defaults()` per-env в `AppServiceProvider::boot()`: production — `min(12)->mixedCase()->numbers()->symbols()->uncompromised()` (HaveIBeenPwned); local/test — `min(8)`. Применяется в reset, profile change, admin set-password, accept invitation. Pest: `tests/Unit/PasswordPolicyTest.php`.
> - **History of N.** Новая таблица `password_histories(user_id FK cascade, password_hash, created_at, index(user_id, created_at))`. Размер окна — `config('rbac.password_history.size')`, дефолт 5, env `RBAC_PASSWORD_HISTORY_SIZE`; 0 отключает проверку. Action `AssertPasswordNotReused` отвергает совпадение с текущим паролем или с любым из последних N хешей; action `RecordPasswordHistory` пишет новый хеш и обрезает хвост. Подключено ко всем точкам смены пароля: profile, admin override, public reset (pre-flight чтобы не «потратить» reset-токен зря), accept invitation. Pest: `tests/Feature/PasswordHistoryTest.php` (9 кейсов).
>
> _Не реализовано (отдельная M-задача):_ **max-age 90 days** требует поля `users.password_changed_at` + middleware/cron, который форсирует ротацию. Это смена UX (модал «срок пароля истёк»), поэтому вынесено в следующую итерацию.

---

## 2. Авторизация и RBAC: расширения модели

### 2.1 Wildcard permissions ✅ (P1, M) — _Сделано_

**Зачем.** Сейчас, чтобы дать "всё по deals", нужно отметить 6 чекбоксов. Wildcard `deals.*` позволит компактно описать "module-wide".

**Что менять.**
- В `permissions` добавить булевый `is_wildcard` (slug формата `deals.*`).
- `ResolveUserPermissions::resolve` после сбора slugs строит `wildcardModules = filter('*')` и при проверке `hasPermission('deals.update')` сначала смотрит exact, потом wildcard.
- В seed добавить wildcard для каждого модуля.
- UI: в редакторе роли выделить wildcard-чекбокс — "give all".

> **Реализовано.** Колонка `permissions.is_wildcard` (миграция `add_is_wildcard_to_permissions_table`), `PermissionSeeder` создаёт по одной wildcard-записи `module.*` на каждый модуль. В enum `App\Enums\Permission` добавлены `modules()`, `wildcardSlugs()`, `isWildcard()`, `expandWildcards()`. `ResolveUserPermissions` раскрывает wildcard в конкретные slug-и на стороне grant и deny, так что кэш остаётся плоской map — `TenantAuthorizer` не меняется и deny-override продолжает работать поверх wildcard. `SyncRolePermissions` принимает `module.*` и для не-супер-админа проверяет, что актёр владеет всеми конкретными правами, в которые раскрывается wildcard. UI: в редакторе роли на каждый модуль добавлен чекбокс «`module.*` (grant all)». Pest: `tests/Feature/WildcardPermissionTest.php`.

### 2.2 Permission groups / bundles ✅ (P2, S) — _Сделано_

Сущность `permission_groups(slug, name)` + pivot `permission_group_permission`. UI позволяет назначать роли сразу группу — облегчает повседневный admin.

> **Реализовано.** Таблицы `permission_groups(tenant_id nullable, slug, name, description)` и `permission_group_permission`, модель `PermissionGroup`. `PermissionGroupSeeder` заводит 4 глобальных бандла (`crm-read-only`, `crm-full`, `user-administration`, `audit-access`). Действие `ApplyPermissionGroupToRole` аддитивно домерживает права бандла к роли, переиспользуя все гарды `SyncRolePermissions` (защита системных ролей и «нельзя выдать то, чем не владеешь»). UI: на странице редактирования роли — селектор «Apply a permission bundle». Pest: `tests/Feature/PermissionGroupTest.php`.

### 2.3 Role inheritance ✅ (P1, L) — _Сделано_

**Зачем.** Сейчас 5 ролей не наследуются: `tenant-admin` всё перечисляет вручную. Введение `roles.parent_id` (nullable) → permission resolver рекурсивно собирает permissions из цепочки родителей.

**Изменения.**
- Миграция `add parent_id to roles, foreign key self, nullOnDelete`.
- Защита от циклов (depth limit + check в action).
- `ResolveUserPermissions::resolve`: загрузить роли + всех предков, объединить permissions.
- `RoleRegistry`: `manager.parent_slug = 'sales'`, `tenant-admin.parent_slug = 'manager'` и т.д.

> **Реализовано.** Миграция `add_parent_id_to_roles_table` (self-FK, `nullOnDelete`). `Role::selfAndAncestors()` и `ResolveUserPermissions::roleClosureIds()` обходят цепочку родителей с защитой от циклов и depth-limit 20. `RoleDefinition` получил `parentSlug`; `RoleRegistry` задаёт цепочку **viewer ← sales ← manager ← tenant-admin** (auditor — без родителя), а `BootstrapTenant` во втором проходе проставляет `parent_id`. Явные списки прав системных ролей оставлены без изменений (наследование аддитивно, union тот же → нулевой риск регрессии). Cycle-safe `SetRoleParent` валидирует self/cross-tenant/cycle и сбрасывает кэш для роли и всех её потомков. UI: карточка «Inheritance» с селектором родителя в редакторе роли. Pest: `tests/Feature/RoleInheritanceTest.php`.

### 2.4 Resource-based / ABAC layer ✅ (P1, L) — _Сделано_

**Зачем.** Сейчас контекстные правила (бизнес-часы, owner, department) живут в Policy-коде. Это негибко. Хочется задавать правила декларативно для каждой роли/permission.

**Идея.** Таблица `permission_conditions(permission_id, role_id, conditions_json)`. Условия — JSON DSL:

```json
{
  "all": [
    {"attr": "deal.status", "op": "=", "value": "draft"},
    {"any": [
      {"attr": "deal.owner_id", "op": "=", "value": "$user.id"},
      {"attr": "deal.department_id", "op": "=", "value": "$user.department_id"}
    ]}
  ]
}
```

Класс `ConditionEvaluator::satisfies($context, $conditions): bool`. Используется в `TenantAuthorizer` после проверки прав.

> **Реализовано.** Таблица `permission_conditions(tenant_id?, permission_id, role_id?, conditions json, description)`. Движок `App\Authorization\ConditionEvaluator` поддерживает группы `all`/`any`/`not`, листья `{attr, op, value}`, операторы `= != > < >= <= in not_in contains` и ссылки на контекст через `$` (например `"$user.id"`). Гейт `App\Authorization\AbacGate` подключён в `TenantAuthorizer::allows` **после** успешной проверки права: собирает применимые условия для (permission, tenant, активные роли пользователя) и требует, чтобы **все** они выполнялись (выбран вариант AND). Слой строго **аддитивный** — право без условий ведёт себя как раньше; есть кэш `rbac:abac:conditioned_slugs` для быстрого short-circuit. Контекст содержит `user.*`, `resource.*` и snake-имя модели (`deal.*`). UI: tenant-admin (право `permissions.assign`) управляет условиями на `admin/permission-conditions` (JSON-редактор с валидацией структуры, список, удаление) + DSL-шпаргалка. Демо-условие в сидере: «closed-сделку нельзя удалять». Pest: `tests/Feature/AbacConditionTest.php`.

### 2.5 Permission preview / diff ✅ (P2, M) — _Сделано_

UI на странице редактирования роли показывает **разницу** между текущим и сохраняемым набором + список пользователей с этой ролью, которые после save потеряют/приобретут доступ. Запрос-эффект → меньше ошибок.

> **Реализовано.** На странице редактирования роли: (1) клиентский индикатор «Unsaved: +N / −M», сравнивающий чекбоксы с исходным состоянием в реальном времени; (2) после сохранения сервер отдаёт точный diff `perm_diff` (added/removed) и отрисовывает блок «Last change» с зелёными/красными slug-ами; (3) панель «Impact» со счётчиком и списком затронутых пользователей (поскольку sync немедленно пересчитывает их доступ). Pest: `tests/Feature/PermissionDiffTest.php`.

### 2.6 Approval workflows ✅ (P1, L) — _Сделано_

**Зачем.** Бизнес требует, чтобы крупные сделки (>=$100k) одобряли два человека (`manager` + `tenant-admin`).

**Что менять.**
- Таблица `approval_requests(id, tenant_id, approvable_type, approvable_id, requested_by, payload json, status enum(pending,approved,rejected), required_steps int, current_step int)`.
- Таблица `approval_steps(request_id, step, approver_role_id|approver_user_id, decided_by, decided_at, decision, note)`.
- DealController.approve: если `deal.amount >= threshold` → создаёт `ApprovalRequest` и помещает deal в стадию `approval_pending` вместо немедленного approve.
- UI: вкладка "Approvals queue" в admin меню.

> **Реализовано.** Таблицы `approval_requests(tenant_id, approvable morph, requested_by, status[pending/approved/rejected], current_step, payload)` и `approval_steps(approval_request_id, step, approver_role_id, decided_by, decided_at, decision, note)`. Конфиг `rbac.approvals`: `deal_threshold` (дефолт `100000`) и `deal_steps = ['manager','tenant-admin']`. `DealController::approve`: при `amount >= threshold` создаётся `ApprovalRequest` (action `RequestApproval`) и сделка переводится в новый статус `DealStatus::PendingApproval`; ниже порога — мгновенное закрытие как раньше. Решения по шагам — action `DecideApprovalStep`: шаг может закрыть только пользователь с ролью этого шага, **не являющийся инициатором** (separation of duties; выбран строгий вариант), reject → сделка возвращается в `active`, полное одобрение → `won/closed`. Добавлен permission `approvals.view` (есть у `manager` и `tenant-admin`). UI: очередь `crm/approvals` с прогрессом шагов, кнопками Approve/Reject и историей решённых; в сайдбаре пункт «Approvals» с бейджем числа ожидающих решения текущим пользователем; на странице сделки — плашка «Pending approval». Pest: `tests/Feature/ApprovalWorkflowTest.php`.

### 2.7 Time-bound elevated access ✅ (P1, M) — _Сделано_

Аналог `sudo`/JIT-access. Tenant-admin может выдать пользователю **временную роль** на N часов (уже есть `expires_at` в `role_user` — нужно UI и `expires_in` UX).

> **Реализовано.** Действие `GrantTemporaryRole` добавляет **одну** роль через `syncWithoutDetaching` с `expires_at = now()->addHours(N)`, не трогая постоянные назначения; проверяет минимум 1 час, уровень актёра и separation-of-duties для результирующего набора, сбрасывает кэш и пишет аудит `roles_assigned` с флагом `temporary`. Resolver уже отбрасывает протухшие назначения, поэтому права исчезают автоматически. UI: на `admin/users/show` блок «Grant temporary (JIT) role» (роль + часы) и бейдж «expires …» рядом с временными ролями. Pest: `tests/Feature/TemporaryRoleTest.php`.

### 2.8 Custom permission per resource instance ✅ (P2, L) — _Сделано_

ReBAC-стиль: дать пользователю Виктору доступ к **конкретному** deal #123. Таблица `resource_permissions(user_id, permission_id, resource_type, resource_id, expires_at)`. Расширить `TenantAuthorizer::allows` четвёртой стадией — instance permission.

> **Реализовано.** Таблица `resource_permissions(tenant_id, user_id, permission_id, resource_type, resource_id, expires_at?, assigned_by?)` + модель `ResourcePermission` (с unique-индексом на пользователя/право/ресурс). Гейт `App\Authorization\InstancePermissionGate` подключён в `TenantAuthorizer::allows` как **fallback**: если статическое право отсутствует, но передан resource и есть непротухший instance-grant — доступ разрешается. Слой строго **аддитивный** — он только расширяет доступ и не обходит проверки активности/кросс-тенанта. Actions `GrantResourcePermission` / `RevokeResourcePermission` (с аудитом). UI: на странице сделки блок «Instance permissions (ReBAC)» (для держателей `permissions.assign`) — выдать пользователю право на эту сделку с опциональным сроком, список и revoke. Демо: viewer получает `deals.update` на одну конкретную сделку. Pest: `tests/Feature/InstancePermissionTest.php`.

### 2.9 Permission audit / unused tracking ✅ (P2, M) — _Сделано_

Cron-команда `rbac:usage` — раз в день сканирует `audit_logs.permission_denied` за 30 дней и в `super-admin/permissions` показывает usage stats (`granted X users · checked N times · denied M times`). Помогает чистить мёртвые permissions.

> **Реализовано.** Действие `PermissionUsageReport` считает по каждому slug: число ролей, число прямых grant-ов и число `permission_denied` за окно `config('rbac.usage.window_days')` (дефолт 30); результат кэшируется. Консольная команда `rbac:usage` (флаг `--unused`) печатает таблицу и помечает права, не выданные никому. Каталог `super-admin/permissions` переведён на таблицу со столбцами Roles / Direct users / Denied и подсветкой «unused» (amber) и wildcard-меток. Pest: `tests/Feature/PermissionUsageTest.php`. _Примечание:_ «checked N times» намеренно не логируется (слишком дорого на каждый чек) — вместо него показываем denied-метрику.

### 2.10 Custom roles per tenant: cloning system role ✅ (P2, S) — _Сделано_

Добавить кнопку "Clone from `manager`" в `roles/create` — создаёт новую роль с теми же permissions, но `is_system=false` и `level < own`.

> **Реализовано.** Действие `CloneRole` создаёт `is_system=false` роль с `level = source.level - 1` (или override), копирует permissions и `parent_id`, проверяет уникальность slug и уровень актёра. UI: кнопка «Clone» в строке каждой роли на `admin/roles/index` и блок «Clone an existing role» на `admin/roles/create`; после клонирования — редирект в редактор новой роли. Pest: `tests/Feature/CloneRoleTest.php`.

---

## 3. Audit log и observability

### 3.1 Diff visualization в UI ✅ (P1, M) — _Сделано_

Сейчас `old_values`/`new_values` хранятся, но в `admin/audit/index` не показываются. Сделать "expand row" → side-by-side diff (Tailwind + Alpine.js).

> **Реализовано.** Строки аудита с изменениями стали раскрываемыми (vanilla-JS toggle, без Alpine — фронт проекта без JS-фреймворка). Партиал `admin/audit/_diff.blade.php` рисует side-by-side таблицу `Field / Before / After` (изменённые поля подсвечены `bg-amber-50`, старое — красным, новое — зелёным), плюс блок `Metadata` (pretty JSON) и исходный `url`. Pest: `tests/Feature/AuditObservabilityTest.php`.

### 3.2 Filtering by user/date range ✅ (P1, S) — _Сделано_

В админ-аудите добавить `user_id` selector, range pickers `from / to`. В SQL уже есть индекс `(user_id, created_at)`, использовать.

> **Реализовано.** `Admin\AuditController@index` принимает `user_id`, `from`, `to` (в дополнение к `action`); добавлены `<select>` пользователей тенанта и два `date`-инпута, кнопка _Reset_. Фильтры сохраняются в пагинации через `withQueryString()`. Pest: `tests/Feature/AuditObservabilityTest.php`.

### 3.3 Структурированный пайплайн в Sentry / Datadog ✅ (P1, S) — _Сделано_

`AuditLog::created` event → publish в monolog `audit` channel → переправить в внешний sink (rsyslog/OpenTelemetry collector/Sentry breadcrumb).

> **Реализовано.** `AuditLogObserver` (через `#[ObservedBy]` на модели `AuditLog`) на каждое создание записи зеркалит структурированную запись в Monolog-канал `audit` (новый `daily`-канал в `config/logging.php`, имя берётся из `config('audit.log_channel')`). Канал можно перенаправить на внешний коллектор (Datadog agent, rsyslog, OpenTelemetry, Sentry breadcrumb) штатной конфигурацией logging без изменения кода. Pest: `tests/Feature/AuditObservabilityTest.php`.

### 3.4 Audit log retention + archive ✅ (P1, M) — _Сделано_

Cron `audit:archive` ежедневно перемещает записи `created_at < now()->subDays(90)` в JSONL-файл на S3 (по тенанту) и удаляет из БД. Настраивается per tenant: `tenants.settings.audit_retention_days`.

> **Реализовано.** Команда `audit:archive` (`{--tenant=}`, `{--dry-run}`) для каждого тенанта берёт окно из `tenants.settings.audit_retention_days` (иначе `config('audit.retention.default_days')`, по умолчанию 90; `0` = retention выключен), выгружает старые строки в JSONL-файл на диск `config('audit.retention.disk')` по пути `audit-archive/{slug}/{timestamp}.jsonl`, удаляет их в транзакции и пишет аудит `audit_archived`. Запланирована в `routes/console.php` ежедневно в 02:00. Pest: `tests/Feature/AuditObservabilityTest.php`.

### 3.5 Per-tenant audit channel ✅ (P2, M) — _Сделано_

Возможность включить **Webhook** или **Kafka topic** для аудита → клиент в реальном времени видит у себя в SIEM. Таблица `audit_sinks(tenant_id, type, config, is_active)`.

> **Реализовано (webhook).** Таблица `audit_sinks` + модель `AuditSink` (`name`, `type`, `endpoint`, `secret`, `events[]`, `is_active`, поля доставки). `AuditLogObserver` веером раздаёт каждую запись активным sink'ам тенанта (фильтр по whitelist `events`, пустой = все действия) через queued job `DeliverAuditLogToSink`. Доставка — подписанный POST (`X-Audit-Signature: sha256=hmac`), с обновлением `last_delivered_at`/`last_failed_at`/`last_error` и ретраями (`config('audit.sinks.tries')`). UI `admin/audit-sinks` (CRUD, gate `audit.manage`). Pest: `tests/Feature/AuditObservabilityTest.php`.

### 3.6 Critical-action confirmation ✅ (P1, S) — _Сделано_

Перед `role.delete`, `user.delete`, `tenant.suspend` показывать модал с подтверждением пароля. Реализовать middleware `password.confirm` (есть в Laravel).

> **Реализовано.** `Auth\ConfirmPasswordController` + страница `auth/confirm-password`, маршруты `password.confirm` (GET/POST, throttle). Middleware `password.confirm` навешен на деструктивные маршруты: `super-admin.tenants.toggle` (suspend/activate), `admin.roles.destroy`, `admin.users.password.update`, `admin.audit-sinks.destroy`. Pest: `tests/Feature/AuditObservabilityTest.php`.

### 3.7 Application monitoring ✅ (P1, M) — _Сделано (lightweight)_

Установить `laravel/pulse` + `laravel/telescope` (dev). Dashboard для query latency, queue depth, slow requests, exception rate.

> **Реализовано (без сторонних пакетов).** `SuperAdmin\ObservabilityController` + страница `super-admin/observability`: KPI-карточки (активные тенанты, пользователи, заблокированные аккаунты, аудит-события за 24ч, неудачные логины, отказы в доступе, failed jobs), bar-chart объёма аудита за 14 дней, топ-действия за 24ч и лента последних security-событий (`login_failed`/`permission_denied`/`account_locked`). Доступ только super-admin. Тяжёлые Pulse/Telescope осознанно отложены (избыточны для текущего масштаба, добавляют инфра-зависимости). Pest: `tests/Feature/AuditObservabilityTest.php`.

### 3.8 Real-time activity feed ✅ (P2, M) — _Сделано (polling)_

Под `Dashboard` показать "Live activity" — broadcast event на канал `tenants.{id}.activity` через Laravel Reverb. Видно "Alice updated Deal X", "Bob completed Task Y".

> **Реализовано (polling вместо WebSocket).** JSON-эндпоинт `tenant.activity-feed` (gate `audit.view`, инкрементальная отдача через `?after={id}`), виджет «Live activity» на дашборде опрашивает его каждые 10с, подсвечивает новые события и держит «пульс» статуса соединения. Выбран polling вместо Reverb, чтобы не тянуть отдельный WebSocket-сервер; апгрейд до broadcasting тривиален при необходимости. Pest: `tests/Feature/AuditObservabilityTest.php`.

---

## 4. Производительность и инфраструктура

### 4.1 Redis cache для permission resolver ✅ (P0, S) — _Сделано_

Заменить `database` cache на `redis` (через `phpredis`/`predis`). Конкретно для permissions кэш-хит порядка 0.2ms против 5-20ms на БД. Добавить `php artisan rbac:warm-cache` для прогрева после деплоя.

> **Реализовано.** Docker-окружение переключает `CACHE_STORE`/`SESSION_DRIVER`/`QUEUE_CONNECTION` на `redis` (контейнер `redis:7`, расширение `phpredis` собрано в образе). Resolver (`ResolveUserPermissions`) кэширует через стандартный `cache()`-репозиторий, поэтому работает с любым стором без изменений. Добавлена команда `php artisan rbac:warm-cache {--tenant=}` — заранее резолвит и кэширует права всех активных не-super-admin пользователей (прогрев после деплоя/сброса кэша). Локально вне Docker по-умолчанию остаётся `database`-стор (Herd/тесты не затронуты). Pest: `tests/Feature/RbacMaintenanceTest.php`.

### 4.2 Queue worker для тяжёлых задач ✅ (P1, M) — _Сделано_

Перевести в очередь:
- **Audit export** (CSV stream работает синхронно, для >100k записей задушит запрос). Сделать `ExportAuditLog` job → пишет в storage и шлёт email со ссылкой.
- **InviteUser**: отправка email (когда mail-driver не log).
- **Bulk import** (см. 5.4).
- **Webhook delivery** (см. 6.3).

Добавить supervised worker через `php artisan queue:work` + healthcheck в Docker.

> **Реализовано.** Инфраструктура: в compose отдельные контейнеры `queue` (`php artisan queue:work --tries=3 --timeout=90`, liveness-healthcheck `pgrep -f queue:work`) и `scheduler` (`php artisan schedule:work`). Прикладные задачи переведены в очередь:
> - **Audit export** → синхронный stream заменён на job `App\Jobs\ExportAuditLog` (ShouldQueue): пишет CSV на диск `local` в `audit-exports/{tenant}/…`, затем шлёт `App\Notifications\AuditExportReady` (тоже queued) с **подписанной** ссылкой на скачивание (route `admin.audit.export.download`, middleware `signed`, живёт 3 дня, скачивание дополнительно гейтится правом `audit.export`). Контроллер теперь сразу возвращает flash «export queued».
> - **InviteUser** → отправка письма приглашения вынесена в on-demand queued-нотификацию `App\Notifications\InvitationNotification` (приглашаемый ещё не User).
> - **Webhook delivery** (`DeliverAuditLogToSink`) уже выполнялся через очередь.
> - Bulk import — вместе с фичей импорта (5.4), пока не реализован.
>
> Pest: `tests/Feature/AuditExportTest.php` (job пишет файл + шлёт нотификацию, подписанная ссылка скачивается, неподписанная — 403), `tests/Feature/InvitationFlowTest.php` (нотификация уходит), `tests/Feature/FeatureFlagTest.php` (export ставится в очередь).

### 4.3 N+1 query audit ✅ (P1, S) — _Сделано_

Pre-deploy запускать `barryvdh/laravel-debugbar` в dev и фиксировать N+1. В `tests/` использовать `assertQueryCount` или `Laravel\Pulse::record`. Особенно проверить:
- `admin/users/index` (eager load roles+department — уже есть)
- `dashboard` recent deals (уже eager)
- `admin/audit/index` (user уже eager)

> **Реализовано.** `barryvdh/laravel-debugbar` добавлен в `require-dev` (автоматически активен в `local`, выключен в `testing`/`production`) — даёт счётчик запросов и таймлайн для ручного аудита перед деплоем. Регрессии зафиксированы тестами `tests/Feature/QueryPerformanceTest.php`: счётчик запросов на `admin/users/index`, `dashboard` и `admin/audit/index` замеряется через `DB::getQueryLog()` при нескольких связанных строках и проверяется на верхнюю границу — если eager-loading сломается и число запросов начнёт расти линейно, тест упадёт. Проверено, что списки уже грузят связи (`users` → `roles`+`department`, dashboard-deals и audit → `user`).

### 4.4 Read replicas (P2, L)

`database.connections.sqlite_replica` (или mysql). `audit_logs.index` идёт в replica. Большая выгода при >1M записей.

### 4.5 Database indexes review ✅ (P1, S) — _Сделано_

Аудит-проверка слабых индексов:
- `permission_user.expires_at` — есть, но при resolve мы фильтруем по `(user_id, expires_at, type)` — добавить composite.
- `role_user.expires_at` — то же самое.
- `companies/contacts/deals (tenant_id, owner_id, status)` — есть, но для CRM-фильтров (`(tenant_id, stage, status)` для воронки) добавить ещё пары.
- Полнотекстовый индекс на `companies.name, contacts.first/last_name` если перейдёте на MySQL.

> **Реализовано.** Миграция `2026_06_17_120000_add_performance_indexes` добавляет составные индексы:
> - `permission_user (user_id, type, expires_at)` — покрывает весь предикат резолва прямых прав одним индексом;
> - `role_user (user_id, expires_at)` — фильтр активных ролей по сроку;
> - `deals (tenant_id, stage, status)` — воронка/pipeline-фильтры.
>
> Индексы кросс-СУБД (работают на SQLite и MySQL). **Полнотекстовый индекс намеренно не добавлен** — он несовместим с SQLite (на нём гоняются тесты) и нужен только под полнотекстовый поиск (8.3), которого пока нет; добавится вместе с поиском.

### 4.6 Horizontal scaling readiness ✅ (P2, M) — _Сделано_

- `SESSION_DRIVER=redis`.
- `CACHE_STORE=redis`.
- `QUEUE_CONNECTION=redis`.
- Stateless сервера за балансировщиком, sticky-session не нужен.
- Health-check `/up` уже есть.

> **Реализовано.** Docker-окружение демонстрирует stateless-готовность: session/cache/queue вынесены в Redis, состояние не хранится в инстансе приложения (app-контейнеры взаимозаменяемы, sticky-session не нужен), очередь и планировщик — отдельные процессы. Health-check `/up` уже присутствует и используется. Для прод-масштабирования достаточно поднять несколько реплик `app`/`queue` за балансировщиком.

### 4.7 Tenant-aware caching keys ✅ (P2, S) — _Сделано_

Все cache keys должны префиксироваться tenant_id. Сейчас `rbac:tenant:{id}:user:{id}:permissions` — корректно. Для других кэшей (например, list of companies) — внедрить хелпер `tenantCacheKey('companies.list')`.

> **Реализовано.** Добавлен глобальный хелпер `tenant_cache_key('companies.list')` (плюс camelCase-алиас `tenantCacheKey()`) в `app/Support/helpers.php` (подключён через `composer.json` → `autoload.files`). Он строит ключ вида `tenant:{id}:{suffix}`, резолвя tenant из request-`Context` (ставится `ResolveTenant`-middleware) с фолбэком на `auth()->user()->tenant_id`; для console/queue-контекста можно передать id явно. Применён как пример к кэшу выпадающего списка действий на `admin/audit/index` (`tenant_cache_key('audit.actions')`, TTL 5 мин) — убирает `DISTINCT`-скан по `audit_logs` на каждый просмотр. RBAC-ключ резолвера прав уже был корректно tenant-scoped. Pest: `tests/Unit/TenantCacheKeyTest.php`.

### 4.8 Scheduled cache cleanup ✅ (P2, S) — _Сделано_

Cron-команда удаляющая просроченные `role_user.expires_at`/`permission_user.expires_at` (мягко — `forceDelete`) + сбрасывающая cache владельцев. Уже это работает через TTL, но БД пухнет.

> **Реализовано.** Команда `php artisan rbac:prune-expired {--dry-run}` удаляет просроченные строки из `role_user`/`permission_user` и сбрасывает кэш прав затронутых пользователей (`ForgetUserPermissionsCache`). Запланирована ежедневно в 03:00 (`routes/console.php`), `--dry-run` показывает объём без удаления. В Docker исполняется контейнером `scheduler`. Pest: `tests/Feature/RbacMaintenanceTest.php`.

### 4.9 Docker + docker-compose dev ✅ (P1, M) — _Сделано_

`docker-compose.yml` с сервисами `app (php-fpm 8.3)`, `nginx`, `redis`, `mysql:8`, `mailpit`, `meilisearch`. Делает onboarding нового разработчика 5-минутным.

> **Реализовано.** `Dockerfile` (php-fpm 8.4 — под Herd-toolchain, расширения `pdo_mysql`/`redis`/`bcmath`/`pcntl`/`zip` + Node 20 + Composer) и `docker-compose.yml` с сервисами `app`, `nginx`, `mysql:8`, `redis:7`, `mailpit`, `dbgate` (универсальный веб-клиент к MySQL и Redis в одном UI, `:3000`), `queue`, `scheduler`, плюс опциональные `vite` (HMR, профиль `dev`) и `test` (Pest на in-memory SQLite, профиль `tools`). Один `entrypoint.sh` обслуживает app/queue/scheduler по `CONTAINER_ROLE`; app-контейнер сам ставит зависимости, ждёт БД, гоняет миграции и (только на свежей БД) сидеры, билдит фронт. `node_modules` вынесен в отдельный volume (`erbac-node-modules`), чтобы macOS-бинарники Herd не конфликтовали с Linux-сборкой. Onboarding — `docker compose up -d --build`, приложение на `http://localhost:8080`, Mailpit UI на `http://localhost:8025`. **Meilisearch намеренно исключён** — в приложении пока нет полнотекстового поиска (8.3). Проверено end-to-end: `/up` и `/login` → 200, redis-сторы активны, воркер healthy, Linux-сборка Vite ок, HMR-`hot`-файл указывает на `localhost:5173`, весь Pest-сьют (162) зелёный через `docker compose run --rm test`. Инструкции — в `README.md`.

---

## 5. CRM-функционал

### 5.1 Kanban-доска для deals (P1, M)

**Зачем.** Текущий список → таблица. Стандарт CRM — drag-and-drop по стадиям.

**Что менять.**
- View `crm/deals/board.blade.php` с 6 колонками (по `DealStage`).
- Alpine.js + native HTML5 drag/drop, PATCH `crm.deals.update-stage` обновляет `stage` через политику.
- Per-tenant настройка цвета стадий.

### 5.2 Pipeline analytics ✅ (P1, M) — _Сделано_

Feature flag `advanced_analytics` уже есть — реализовать страницу `dashboard/analytics`:
- Конверсия по стадиям (funnel chart).
- Total amount per owner.
- Average deal cycle.
- Win/loss reasons (новое поле `deals.lost_reason`).

Графики через `Chart.js`-блейд-компонент. Возможен экспорт PDF (см. 5.10).

> **Реализовано.** Новый permission `reports.view` (модуль `reports`, выдан manager + tenant-admin) и поле `deals.lost_reason` (миграция `add_lost_reason_to_deals_table`, обязательно при `stage = lost` через `DealRequest`). Действие `App\Actions\Reports\PipelineAnalytics` единым tenant-scoped запросом считает воронку (count + amount по стадиям), сумму сделок по владельцам, средний цикл выигранных сделок и win/loss-разбивку по причинам — результат потребляют и HTML-страница, и PDF, поэтому цифры не расходятся. Страница `crm.reports.analytics` рисует funnel/owner-графики через `Chart.js` (CDN, новый `@stack('scripts')` в layout), ссылка в сайдбаре скрыта за фичей + правом. Маршруты в группе `feature:advanced_analytics` + `permission:reports.view`. Pest: `tests/Feature/ReportsTest.php`.

### 5.3 Custom fields ✅ (P0, L) — _Сделано_

**Зачем.** Каждому тенанту нужны свои поля (e.g. "Renewal date", "Account manager", "Region").

**Архитектура.**
- Таблица `custom_field_definitions(id, tenant_id, model_type, key, label, type enum(text,number,date,select,boolean,user), options json, required, position)`.
- Таблица `custom_field_values(definition_id, owner_id, owner_type, value_text, value_number, value_date, value_json)` — EAV.
- `HasCustomFields` trait добавляет `customFields(): morphMany` и accessor `getCfAttribute($key)`.
- UI: admin → "Custom fields" редактор; на формах CRM рендерятся динамически.
- Form validation generates rules from definitions.

> **Реализовано.** EAV-подсистема для всех CRM-сущностей (Company, Contact, Deal, Task). Миграции `create_custom_field_definitions_table` (unique `tenant_id+model_type+key`) и `create_custom_field_values_table` (полиморфный `owner`, колонки `value_text/value_number/value_date/value_json`, unique по definition+owner). Enum `App\Enums\CustomFieldType` (text/number/date/select/boolean/user) инкапсулирует целевую колонку, правила валидации и приведение типа. Трейт `HasCustomFields` даёт `customFieldValues(): morphMany` + хелпер `cf($key)`. Действие `App\Actions\CustomField\SyncCustomFields` генерирует правила из определений (`custom_fields.{key}`) — валидация выполняется **до** создания записи, поэтому транзакция остаётся целостной — и апсертит значения. Админский редактор `admin/custom-fields` (право `custom-fields.manage`, модуль `custom-fields`) создаёт/редактирует/удаляет поля; формы и страницы show CRM рендерят поля динамически (`crm/_custom-fields*.blade.php`). Pest: `tests/Feature/CustomFieldTest.php`.

### 5.4 Bulk CSV import (P1, M)

Feature flag `bulk_import` уже зарезервирован. Реализовать:
- View `crm/companies/import` — drag-and-drop CSV.
- `ImportCompaniesJob extends ShouldQueue` парсит chunks по 500.
- Маппинг колонок (UI помогает сопоставить CSV-колонки → поля Company).
- Прогресс через `JobBatch` API + Livewire/Alpine refresh.
- Audit log по batch.

### 5.5 Tags / Labels (P1, S)

Полиморфная таблица `taggables(tag_id, taggable_type, taggable_id)` + `tags(tenant_id, name, color)`. Использование `Company::tags()`, `Deal::tags()`. Фильтрация в индексах.

### 5.6 Notes / Comments on resources (P1, M)

`comments(tenant_id, commentable_type, commentable_id, user_id, body, parent_id, created_at)`. UI на странице show `Deal/Company/Contact/Task` — thread с @mentions. Mention триггерит in-app notification.

### 5.7 File attachments ✅ (P0, M) — _Сделано_

Тенант-scoped storage (`storage/app/tenants/{id}/...` или S3 prefix). Полиморфная таблица `attachments(tenant_id, attachable_type, attachable_id, disk, path, name, size, mime, uploaded_by)`. Signed URLs для скачивания. Лимит размера + per-tenant квота.

> **Реализовано.** Миграция `create_attachments_table` (полиморфный `attachable`, `tenant_id`, индекс по tenant+attachable), модель `Attachment` (BelongsToTenant, `humanSize()`), трейт `HasAttachments` подключён к Company/Contact/Deal/Task. Файлы лежат на приватном диске под `tenants/{id}/attachments/...`. Действие `App\Actions\Attachment\UploadAttachment` сохраняет файл, пишет запись и аудит `attachment_uploaded`. `Crm\AttachmentController`: `store` (whitelist коротких ключей сущностей вместо сырого класса, проверка `update` на родителе, лимит файла из `config/attachments.php` и per-tenant квота), `download` (только по подписанному URL + право `view` на родителе), `destroy` (право `update`, аудит `attachment_deleted`). Виджет аплоада/списка добавлен на страницы show всех четырёх сущностей. Pest: `tests/Feature/AttachmentTest.php`.

### 5.8 Recurring tasks (P2, M)

Добавить `tasks.recurrence_rule (string, RFC 5545 RRULE)`, `tasks.recurrence_parent_id`. Cron `tasks:generate-recurring` создаёт следующий instance после `completed_at`.

### 5.9 Task dependencies & subtasks (P2, M)

Self-FK `tasks.parent_id`. Логика "не закрыть task, пока subtasks не done". UI tree-view.

### 5.10 PDF generation для отчётов ✅ (P1, M) — _Сделано_

`barryvdh/laravel-dompdf` (или `spatie/browsershot`). Шаблоны Blade. Контроллер `Reports::dealsPdf($tenant, request())` стримит PDF. Используется в pipeline-analytics и quarterly-reports.

> **Реализовано.** Подключён `barryvdh/laravel-dompdf`. `Crm\ReportsController::dealsPdf` берёт те же данные из `PipelineAnalytics`, рендерит самодостаточный Blade-шаблон `crm/reports/deals-pdf.blade.php` (DejaVu Sans, inline-CSS — без внешних ассетов) и стримит PDF; экспорт пишет аудит `report_exported`. Кнопка _Download PDF_ на странице аналитики, тот же гейтинг `feature:advanced_analytics` + `permission:reports.view`. Pest-проверка PDF-ответа — в `tests/Feature/ReportsTest.php`.

### 5.11 Multi-currency и conversion (P2, M)

Таблица `exchange_rates(currency, base_currency, rate, fetched_at)`. Cron подтягивает с `openexchangerates`/`exchangerate-api`. На dashboard суммы конвертируются в `tenant.settings.base_currency`.

### 5.12 Sales forecasting (P2, L)

ML/статистическая модель: `deals * probability` по `expected_close_date`. Команда `forecast:recompute` + кэш. Можно вынести в отдельный микросервис на Python.

### 5.13 Lead capture форма (web-to-lead) (P2, M)

Публичный endpoint `/api/leads/{tenant_token}` (rate-limited) принимает форму с сайта клиента, создаёт `Contact + Deal(stage=lead, status=draft)`. Защита reCAPTCHA / hCaptcha.

### 5.14 Email-to-CRM (mailbox parsing) (P2, L)

Inbound email через Mailgun/Postmark webhook. Парсер находит контакта по email и аппендит `Activity{type=email, body=...}`. Уже есть `subjectable_*` morph — переиспользовать.

### 5.15 Calendar integration ICS (P2, S)

`/t/{tenant}/calendar.ics` (signed URL per user) выдаёт встречи (Activity{type=meeting}) и due_date тасков. Подписка из Google/Apple Calendar.

---

## 6. Уведомления и интеграции

### 6.1 Email notifications (P0, M)

**Проблема.** `MAIL_MAILER=log` → инвайт письма не приходят, password-reset тоже.

**Что менять.**
- Шаблоны `mail/invitation.blade.php`, `mail/password-reset.blade.php`, `mail/welcome.blade.php`.
- `Notification` классы (`UserInvitedNotification`, `DealApprovedNotification`).
- Все на `ShouldQueue`.
- Конфиг SMTP/SES в `.env.example`.

### 6.2 In-app notifications (P1, M)

`notifications` таблица (Laravel default). Колокольчик в `topbar.blade.php` показывает unread count. Поллинг каждые 30s или WebSocket через Reverb. Триггеры: новая роль, новая задача мне, упомянули в комментарии, мой deal approved.

### 6.3 Outgoing webhooks (P1, M)

Тенант настраивает webhook в `admin/webhooks`. Подписки на события (`deal.created`, `deal.approved`, `audit.*`).

**Архитектура.**
- `webhooks(id, tenant_id, url, secret, events json, is_active, retry_policy json)`.
- `webhook_deliveries(webhook_id, event, payload, status, attempts, response_code, last_error, deliver_at)`.
- Listener `DispatchWebhook` собирает payload, `DeliverWebhookJob` отправляет с retry/backoff (`retryUntil`).
- HMAC подпись `X-Signature: sha256=...`.

### 6.4 Slack/Teams интеграция (P2, S)

Per-tenant Slack webhook URL → уведомления о крупных деалах, новых инвайтах, security-событиях.

### 6.5 Google/Microsoft 365 SSO + calendar/email sync (P2, L)

Через OAuth токены тенанта получаем доступ к Calendar API и Gmail/Outlook — автоматически создаём `Activity` для встреч и писем участников.

### 6.6 Mobile push notifications (P2, M)

Когда появится мобильное приложение → FCM/APNs. Таблица `device_tokens(user_id, platform, token, last_used_at)`.

---

## 7. API и расширяемость

### 7.1 REST API + Sanctum (P0, M)

**Зачем.** Без API нет интеграций и mobile.

**Архитектура.**
- `routes/api.php` под middleware `auth:sanctum,tenant.api`.
- Контроллеры `Api\V1\*`, JSON resources `App\Http\Resources\*`.
- Per-user `personal_access_tokens` с scopes (по permission slugs).
- Per-tenant rate limit (`60/min` или по плану).
- OpenAPI/Swagger через `darkaonline/l5-swagger`. Документация на `/api/docs`.

### 7.2 Tenant-scoped API tokens (P1, S)

`api_tokens(id, tenant_id, name, hashed_token, scopes json, created_by, last_used_at, expires_at)`. UI в admin → integrations.

### 7.3 GraphQL endpoint (P2, L)

`nuwave/lighthouse`. Преимущество — клиент выбирает поля, меньше запросов в мобильном приложении. Авторизация на уровне field via `@can`.

### 7.4 Plugin/Extension system (P2, L)

Декомпозиция CRM на packages: `enterprise-rbac/core`, `enterprise-rbac/deals`, `enterprise-rbac/billing`, `enterprise-rbac/marketing`. Подключаются по конфигу `config('plugins')` + Service Provider'ы.

### 7.5 Embeddable widget (P2, M)

`<script src="https://app/widgets/contact-form.js?tenant=acme&token=...">` для встраивания CRM-форм на лендинг клиента. Использует Web Components.

---

## 8. UX/UI

### 8.1 Dark mode (P1, S)

Tailwind `dark:` варианты. Toggle в topbar сохраняет в `users.settings.theme`. Sidebar уже тёмный — основной контент инвертировать.

### 8.2 Mobile-responsive (P0, M)

Sidebar становится drawer на `<lg`. Tables → "card list" на mobile. Forms — single-column. Использовать `@tailwindcss/forms` плагин для согласованных полей.

### 8.3 Global search (P1, M)

`Cmd/Ctrl+K` open Spotlight → ищет одновременно Company/Contact/Deal/User. `laravel/scout` + `meilisearch`. Per-tenant index (`tenants_{id}_companies`).

### 8.4 Branding per tenant (P2, S)

`tenants.settings.brand = { logo_url, primary_color, accent_color, app_name }`. Layout рендерит CSS-переменные. Super-admin → Tenant → "Branding" вкладка.

### 8.5 i18n (P2, M)

Laravel localization. Файлы `lang/{en,ru,de,es,fr}/...`. На каждой странице `__(...)` обёрнуть строки. Per-user `users.locale`. Middleware `SetLocale`.

### 8.6 Empty states + onboarding (P2, S)

После создания тенанта показывать чек-лист: invite first user → create company → create deal → toggle features. Helpful tooltips в первом UX-туре (`shepherd.js`).

### 8.7 Saved views / filters (P2, M)

Сохраняемый набор фильтров+columns для индексных страниц. Таблица `saved_views(user_id, model_type, name, query json, shared bool)`. Делиться с командой.

### 8.8 Bulk actions on lists (P1, S)

Чекбоксы в `index` → bulk delete / assign owner / change status / add tag. Authorize через политики (каждый item check).

### 8.9 Toast notifications (P2, S)

Заменить flash `bg-green-50` баннер на нелетающий `aria-live` toast — Alpine `x-data` + transition.

### 8.10 Accessibility audit (P1, S)

axe-core scan, alt-тексты, focus-states, ARIA labels. Особенно важно sidebar nav (`role="navigation"`).

### 8.11 Dashboard widgets (per user) (P2, M)

Юзер компонует dashboard из widget'ов: "my deals", "my tasks", "team activity", "top customers". Хранится в `users.settings.dashboard`.

---

## 9. Compliance, GDPR, ретеншн

### 9.1 Right to erasure (P0, M)

UI кнопка "Delete account" в user profile. Backend:
- Заменяет PII в `users` (name → "Deleted user", email → "deleted+{id}@local").
- Soft-delete + cron job `forceDelete` после 30 дней.
- Audit log по запросу — экспорт CSV.

### 9.2 Data export (right to portability) (P1, M)

`/account/export` → job собирает все ресурсы пользователя (companies/deals owned, comments authored, activity logs) в zip. Email-уведомление со ссылкой.

### 9.3 PII encryption at rest (P1, M)

Поля `users.last_login_ip`, `audit_logs.ip_address/user_agent`, `contacts.email/phone` — encrypted cast (`encrypted`). Ключ ротации через `php artisan model:prune-encrypted`.

### 9.4 GDPR consent tracking (P2, S)

При создании контакта — checkbox "marketing consent" + дата + IP. Таблица `consents(contact_id, type, given_at, withdrawn_at, source)`.

### 9.5 Data retention policies (P1, M)

Per-tenant: `tenants.settings.retention = { audit: 90, deals_closed: 365, activities: 730 }`. Cron-команды чистят по политике.

### 9.6 SOC 2 / ISO 27001 readiness (P2, L)

- Полный audit trail (✅ есть).
- Encryption at rest (см. 9.3) + at transit (HTTPS).
- Принципы least privilege (RBAC ✅).
- Backup retention (см. ниже).
- Incident response plan + runbooks (документация).

### 9.7 Backups (P0, S)

`spatie/laravel-backup` → S3 ежедневно (DB dump + `storage/app`), retention 30 дней, alert при провале. Восстановление документировано.

### 9.8 Anonymization для тестового окружения (P2, S)

Команда `db:anonymize` для seeding staging — заменяет email, имена, телефоны faker-данными.

---

## 10. Платформа SaaS и биллинг

### 10.1 Self-service tenant signup (P1, M)

`/register/tenant` → форма name + slug + admin email/password. Создаёт `Tenant + User(tenant-admin)` + email verification. `is_active=false` пока не подтверждён email + не выбран план.

### 10.2 Subscription billing (P1, L)

`laravel/cashier` (Stripe). Per-tenant plan (`free, starter, pro, enterprise`). Лимиты:
- max users
- max companies
- audit retention
- доступные feature flags

`enforceBillingLimits` middleware блокирует операции при превышении лимита. Webhook от Stripe → активирует/деактивирует фичи.

### 10.3 Usage metering (P2, M)

`usage_records(tenant_id, metric, value, recorded_at)`. Метрики: `api_calls`, `audit_log_entries`, `storage_bytes`. Pro-rated billing.

### 10.4 Plan-based feature flags (P1, S)

Feature flags уже есть, но привязать их к плану: `plan_features(plan_id, feature_id, default_enabled)`. При смене плана автоматически синхронизируется.

### 10.5 Multi-region (P2, L)

Шардирование по гео-региону тенанта (`tenants.region: us/eu/apac`). Маршрутизация на ближайший кластер. Для compliance — данные EU остаются в EU.

### 10.6 Subdomain-based tenancy (P2, M)

Параллельно с `/t/{slug}` поддержать `{slug}.app.example.com`. Wildcard SSL. Middleware `ResolveTenantBySubdomain` имеет приоритет над path-based.

### 10.7 Trial периоды + extension (P2, S)

`tenants.trial_ends_at`. Banner показывает оставшиеся дни. После окончания: read-only mode либо blocked.

---

## 11. DevEx и качество кода

### 11.1 CI/CD pipeline (P0, S)

`.github/workflows/ci.yml`:
- PHP `^8.3` matrix.
- `composer install --no-progress`.
- `vendor/bin/pint --test`.
- `php artisan test --parallel --compact`.
- Coverage upload `coverage.xml` → Codecov.
- `npm run build` smoke.
- Deploy на `main` через SSH/Forge/Vapor.

### 11.2 Static analysis: PHPStan/Larastan level max (P1, M)

`phpstan.neon` с `larastan/larastan:^3.0`, level 8, исключения только для генерируемого кода. Запуск в CI.

### 11.3 Pest architecture tests (P1, S)

```php
arch('controllers')
    ->expect('App\\Http\\Controllers')
    ->classes()->toExtend(App\Http\Controllers\Controller::class);

arch('policies')
    ->expect('App\\Policies')
    ->classes()->toBeFinal()->toBeReadonly();

arch('actions')
    ->expect('App\\Actions')
    ->classes()->toBeFinal()->toBeReadonly()
    ->toHaveMethod('handle');

arch('no debug')
    ->expect(['dd', 'dump', 'ddd', 'ray'])->not->toBeUsed();
```

### 11.4 Mutation testing (P2, M)

`infection/infection` → MSI > 80% на ключевых неймспейсах (`App\Authorization`, `App\Actions\Authorization`). Запуск раз в неделю.

### 11.5 Code coverage цели (P1, S)

CI fail если coverage < 80% по `app/Authorization`, `app/Actions`, `app/Policies`. Сейчас 56 тестов — добавить ещё ~30 для покрытия Controllers (например, `RoleController::syncPermissions` happy + escalation).

### 11.6 Type-safer JSON casts (P2, S)

`tenants.settings`, `audit_logs.metadata` — сейчас generic array. Использовать [Custom Casts](https://laravel.com/docs/eloquent-mutators) с DTO-объектами (`Spatie\LaravelData`).

### 11.7 DTOs and validators consolidation (P2, M)

Использовать `spatie/laravel-data` вместо `FormRequest` + `array $payload`. Преимущества: type-safe, OpenAPI generation, реюзабельные DTO.

### 11.8 Telescope в local + Pulse в production (P1, S)

Telescope: debug queries/jobs/notifications/cache. Pulse: production health.

### 11.9 Документация для контрибьюторов (P2, S)

`docs/` папка с архитектурными решениями (ADR), диаграммой permission pipeline, гайдами на новую permission/новый module.

### 11.10 Pre-commit hook (P2, S)

`.husky` или `laravel/installer`-стиль hook: `pint --dirty` + `pest --filter=changed`.

### 11.11 Database seeders для тестовых сценариев (P2, S)

Помимо `DemoTenantSeeder`, добавить `LoadTestSeeder` который создаёт 10 тенантов × 50 пользователей × 500 deals каждый — для нагрузочного тестирования.

### 11.12 Type-safe routes / Ziggy (P2, S)

`tightenco/ziggy` для frontend → типизированные route() вызовы в JS-коде когда появятся SPA-фичи.

### 11.13 Observable events для мутаций (P2, S)

Вместо ручного `audit->handle(...)` в каждом action → события `RoleAssigned`, `PermissionGranted`, `DealApproved` + listeners. Снижает связность.

### 11.14 Архитектурный декомпоз: Domain / Application / Infrastructure (P2, L)

Сейчас всё лежит в `app/`. Переезд на DDD-стиль: `app/Domain/Authorization`, `app/Application/Actions`, `app/Infrastructure/Persistence`. Снижает плотность зависимостей в долгосроке.

---

## 12. Сводная таблица приоритетов и roadmap по фазам

### Уже реализовано в этом репозитории

| # | Улучшение | Коммит / артефакт |
|---|-----------|-------------------|
| 1.1 | Password reset flow | `feat(auth): self-service password reset via email link` · `Auth\PasswordResetController` + `tests/Feature/PasswordResetTest.php` |
| 1.2 | Email verification (soft) | `feat(auth): email verification in soft mode` · `Auth\EmailVerificationController` + `tests/Feature/EmailVerificationTest.php` |
| 1.5 | Account lockout | `feat(security): account lockout after repeated failed logins` · миграция `add_lockout_fields_to_users_table` + `UnlockUserAccount` + `tests/Feature/AccountLockoutTest.php` |
| 1.7 | Profile + sessions UI | `feat(auth): self-service profile + sessions UI` · `ProfileController` + `ChangeOwnPassword` + `tests/Feature/ProfileTest.php` |
| 1.8 | Security headers middleware | `feat(security): add SecurityHeaders middleware` · `App\Http\Middleware\SecurityHeaders` + `tests/Feature/SecurityHeadersTest.php` |
| 1.9 | Force HTTPS in production | `feat(security): force HTTPS scheme in production` · `AppServiceProvider::boot()` |
| 1.10 | Password policy (per env) | `feat(security): centralize password policy via Password::defaults` · `tests/Unit/PasswordPolicyTest.php` |
| 1.10 | Password history (last N) | `feat(security): password history check` · миграция `create_password_histories_table` + `AssertPasswordNotReused` / `RecordPasswordHistory` + `tests/Feature/PasswordHistoryTest.php` |
| —    | Admin set user password | `feat(admin): super-admin & tenant-admin can reset user passwords` · `SetUserPassword` + `UserPolicy::setPassword` + `tests/Feature/AdminSetPasswordTest.php` |
| 2.1 | Wildcard permissions | `feat(rbac): module.* wildcard permissions` · миграция `add_is_wildcard_to_permissions_table` + `Permission::expandWildcards` + `tests/Feature/WildcardPermissionTest.php` |
| 2.2 | Permission groups / bundles | `feat(rbac): permission bundles applied to roles` · `permission_groups` + `ApplyPermissionGroupToRole` + `tests/Feature/PermissionGroupTest.php` |
| 2.3 | Role inheritance | `feat(rbac): role inheritance via parent_id` · миграция `add_parent_id_to_roles_table` + `SetRoleParent` + resolver ancestor-walk + `tests/Feature/RoleInheritanceTest.php` |
| 2.5 | Permission preview / diff | `feat(rbac): role edit impact + permission diff` · `RoleController::syncPermissions` diff + impact panel + `tests/Feature/PermissionDiffTest.php` |
| 2.7 | Time-bound elevated access | `feat(rbac): JIT temporary role grants` · `GrantTemporaryRole` + users.show UI + `tests/Feature/TemporaryRoleTest.php` |
| 2.9 | Permission usage tracking | `feat(rbac): rbac:usage report + catalog stats` · `PermissionUsageReport` + `rbac:usage` command + `tests/Feature/PermissionUsageTest.php` |
| 2.10 | Clone system role | `feat(rbac): clone roles into editable copies` · `CloneRole` + roles index/create UI + `tests/Feature/CloneRoleTest.php` |

### Сводная таблица

> Реализованные пункты помечены галочкой `✅` в колонке «Улучшение»; частично реализованные (готова инфраструктура) — `🟡`. Подробности — в соответствующих разделах выше.

| # | Улучшение | Приоритет | Сложность | Фаза |
|---|-----------|-----------|-----------|------|
| 1.1 | Password reset ✅ | P0 | S | 1 |
| 1.5 | Account lockout ✅ | P1 | S | 1 |
| 1.8 | Security headers ✅ | P1 | S | 1 |
| 1.9 | Force HTTPS ✅ | P0 | S | 1 |
| 4.1 | Redis cache ✅ | P0 | S | 1 |
| 4.2 | Queue worker ✅ | P1 | M | 2 |
| 4.5 | DB indexes review ✅ | P1 | S | 1 |
| 5.7 | File attachments ✅ | P0 | M | 2 |
| 5.3 | Custom fields (EAV) ✅ | P0 | L | 3 |
| 6.1 | Email notifications | P0 | M | 1 |
| 7.1 | REST API + Sanctum | P0 | M | 2 |
| 9.1 | Right to erasure | P0 | M | 2 |
| 9.7 | Backups | P0 | S | 1 |
| 11.1 | CI/CD pipeline | P0 | S | 1 |
| 1.2 | Email verification ✅ | P1 | S | 2 |
| 1.3 | 2FA | P1 | M | 2 |
| 1.6 | SAML/OIDC SSO | P1 | L | 3 |
| 2.1 | Wildcard permissions ✅ | P1 | M | 2 |
| 2.3 | Role inheritance ✅ | P1 | L | 3 |
| 2.4 | ABAC layer ✅ | P1 | L | 3 |
| 2.6 | Approval workflows ✅ | P1 | L | 3 |
| 2.7 | Time-bound elevated access ✅ | P1 | M | 2 |
| 3.1 | Diff visualization ✅ | P1 | M | 2 |
| 3.2 | Audit filters ✅ | P1 | S | 1 |
| 3.4 | Audit retention/archive ✅ | P1 | M | 2 |
| 3.6 | Critical-action confirmation ✅ | P1 | S | 1 |
| 3.7 | App monitoring ✅ | P1 | M | 2 |
| 4.9 | Docker dev ✅ | P1 | M | 1 |
| 5.1 | Kanban board | P1 | M | 2 |
| 5.2 | Pipeline analytics ✅ | P1 | M | 2 |
| 5.4 | Bulk CSV import | P1 | M | 2 |
| 5.5 | Tags/Labels | P1 | S | 1 |
| 5.6 | Comments | P1 | M | 2 |
| 5.10 | PDF reports ✅ | P1 | M | 2 |
| 6.2 | In-app notifications | P1 | M | 2 |
| 6.3 | Outgoing webhooks | P1 | M | 2 |
| 7.2 | API tokens | P1 | S | 2 |
| 8.1 | Dark mode | P1 | S | 1 |
| 8.2 | Mobile-responsive | P0 | M | 1 |
| 8.3 | Global search | P1 | M | 2 |
| 8.8 | Bulk actions | P1 | S | 1 |
| 8.10 | A11y audit | P1 | S | 1 |
| 9.2 | Data export | P1 | M | 2 |
| 9.3 | PII encryption | P1 | M | 2 |
| 9.5 | Retention policies | P1 | M | 2 |
| 10.1 | Self-service signup | P1 | M | 2 |
| 10.2 | Subscription billing | P1 | L | 3 |
| 10.4 | Plan-based features | P1 | S | 2 |
| 11.2 | PHPStan max | P1 | M | 1 |
| 11.3 | Arch tests | P1 | S | 1 |
| 11.5 | Coverage gate | P1 | S | 1 |
| 11.8 | Telescope/Pulse | P1 | S | 1 |
| 1.4 | WebAuthn | P2 | L | 4 |
| 1.7 | Session UI ✅ | P2 | S | 3 |
| 1.10 | Password policies ✅ | P2 | S | 2 |
| 2.2 | Permission groups ✅ | P2 | S | 3 |
| 2.5 | Permission diff ✅ | P2 | M | 3 |
| 2.8 | Resource-instance perms ✅ | P2 | L | 4 |
| 2.9 | Permission usage tracking ✅ | P2 | M | 3 |
| 2.10 | Role cloning ✅ | P2 | S | 2 |
| 3.3 | Sentry/Datadog ✅ | P1 | S | 2 |
| 3.5 | Per-tenant audit sinks ✅ | P2 | M | 3 |
| 3.8 | Real-time activity feed ✅ | P2 | M | 3 |
| 4.3 | N+1 audit ✅ | P1 | S | 1 |
| 4.4 | Read replicas | P2 | L | 4 |
| 4.6 | Horizontal scaling ✅ | P2 | M | 3 |
| 4.7 | Cache key tenancy ✅ | P2 | S | 2 |
| 4.8 | Cache cleanup cron ✅ | P2 | S | 2 |
| 5.8 | Recurring tasks | P2 | M | 3 |
| 5.9 | Task dependencies | P2 | M | 3 |
| 5.11 | Multi-currency conversion | P2 | M | 3 |
| 5.12 | Sales forecasting | P2 | L | 4 |
| 5.13 | Web-to-lead | P2 | M | 3 |
| 5.14 | Email-to-CRM | P2 | L | 4 |
| 5.15 | ICS calendar | P2 | S | 2 |
| 6.4 | Slack | P2 | S | 2 |
| 6.5 | Calendar sync | P2 | L | 4 |
| 6.6 | Mobile push | P2 | M | 3 |
| 7.3 | GraphQL | P2 | L | 4 |
| 7.4 | Plugin system | P2 | L | 4 |
| 7.5 | Embed widget | P2 | M | 3 |
| 8.4 | Branding | P2 | S | 2 |
| 8.5 | i18n | P2 | M | 3 |
| 8.6 | Onboarding tour | P2 | S | 2 |
| 8.7 | Saved views | P2 | M | 3 |
| 8.9 | Toasts | P2 | S | 1 |
| 8.11 | Dashboard widgets | P2 | M | 3 |
| 9.4 | GDPR consent | P2 | S | 2 |
| 9.6 | SOC2 readiness | P2 | L | 4 |
| 9.8 | Anonymize seeder | P2 | S | 2 |
| 10.3 | Usage metering | P2 | M | 3 |
| 10.5 | Multi-region | P2 | L | 4 |
| 10.6 | Subdomain tenancy | P2 | M | 3 |
| 10.7 | Trials | P2 | S | 2 |
| 11.4 | Mutation testing | P2 | M | 3 |
| 11.6 | DTO casts | P2 | S | 2 |
| 11.7 | spatie/laravel-data | P2 | M | 3 |
| 11.9 | Docs/ADR | P2 | S | 1 |
| 11.10 | Pre-commit hook | P2 | S | 1 |
| 11.11 | Load-test seeder | P2 | S | 2 |
| 11.12 | Ziggy | P2 | S | 3 |
| 11.13 | Observable events | P2 | S | 2 |
| 11.14 | DDD layering | P2 | L | 4 |

### Roadmap по фазам

**Фаза 1 — «Готовность к продакшну» (1-2 недели работы команды из 2 разработчиков).**

Основа без которой нельзя выпускать в prod:

- Безопасность: 1.1 password reset · 1.9 force HTTPS · 1.8 security headers · 1.5 account lockout.
- Инфраструктура: 4.1 redis cache · 4.5 индексы · 4.9 docker · 9.7 backups.
- Email: 6.1 email notifications (без них инвайты не работают).
- UX: 8.1 dark mode · 8.2 mobile · 8.8 bulk actions · 8.10 a11y · 8.9 toasts.
- DevEx: 11.1 CI · 11.2 PHPStan · 11.3 arch tests · 11.5 coverage gate · 11.8 telescope/pulse · 11.9 docs · 11.10 pre-commit.
- Audit: 3.2 filters · 3.6 confirm.
- 5.5 tags · 4.3 N+1 audit.

**Фаза 2 — «Enterprise feature parity» (1-2 месяца).**

- Auth: 1.2 verify email · 1.3 2FA · 1.10 password policies.
- RBAC: 2.1 wildcard · 2.7 time-bound · 2.10 clone роли.
- Audit: 3.1 diff · 3.3 Sentry · 3.4 retention · 3.7 monitoring.
- CRM: 5.1 kanban · 5.2 analytics · 5.4 import · 5.6 comments · 5.7 attachments · 5.10 PDF · 5.15 ICS.
- Integrations: 6.2 in-app · 6.3 webhooks · 6.4 Slack.
- API: 7.1 REST · 7.2 tokens.
- Compliance: 9.1 erasure · 9.2 export · 9.3 PII encrypt · 9.4 consent · 9.5 retention · 9.8 anonymize.
- SaaS: 10.1 self-signup · 10.4 plan features · 10.7 trials · 8.4 branding · 8.6 onboarding.
- DevEx: 11.6/11.7 DTOs · 11.13 events · 11.11 load seeder.

**Фаза 3 — «Глубокий enterprise» (3-6 месяцев).**

- Auth: 1.6 SSO · 1.7 session UI.
- RBAC: 2.3 inheritance · 2.4 ABAC · 2.5 diff preview · 2.6 approvals · 2.9 usage stats · 2.2 groups.
- Audit: 3.5 sinks · 3.8 real-time feed.
- CRM: 5.3 custom fields · 5.8 recurring · 5.9 subtasks · 5.11 multi-currency · 5.13 web-to-lead.
- Integrations: 6.6 push.
- API: 7.5 embed widgets.
- UX: 8.3 global search · 8.5 i18n · 8.7 saved views · 8.11 dashboard widgets.
- Платформа: 4.6 horizontal scaling · 4.7 tenant cache keys · 4.8 cache cron · 10.2 billing · 10.3 metering · 10.6 subdomain.
- DevEx: 11.4 mutation · 11.7 spatie/data · 11.12 ziggy.

**Фаза 4 — «Будущее» (6+ месяцев, по бизнес-сигналам).**

- 1.4 WebAuthn.
- 2.8 resource-level permissions.
- 4.4 read replicas.
- 5.12 forecasting · 5.14 email-to-CRM.
- 6.5 calendar sync.
- 7.3 GraphQL · 7.4 plugins.
- 9.6 SOC2 cert.
- 10.5 multi-region.
- 11.14 DDD-разделение.

---

## Приложение A. Топ-10 «Quick wins» (S + P0/P1)

Реализуемы за 1-3 дня каждый, дают максимум ценности:

1. **CI pipeline** (11.1) — гарантия неломаемости.
2. **Email notifications + queue worker** (6.1, 4.2) — без них инвайты бесполезны.
3. **Password reset** (1.1) — обязательная фича.
4. **Redis cache** (4.1) — мгновенное ускорение auth.
5. **Backups** (9.7) — disaster recovery.
6. **Force HTTPS + security headers** (1.9, 1.8) — на прод-домене обязательно.
7. **Mobile responsive sidebar** (8.2 базовая часть) — без неё дашборд недоступен с телефона.
8. **Tags** (5.5) — UX-win с минимальной схемой.
9. **Audit filter by user+date** (3.2) — на тенанта с 1000 записями невозможно работать без фильтров.
10. **Arch + coverage gates** (11.3, 11.5) — не позволят случайно деградировать качество.

## Приложение B. Влияние на схему БД

Минимальные новые таблицы (по фазам):

**Фаза 2:**
- `two_factor_secrets`
- `attachments`
- `comments`
- `tags`, `taggables`
- `notifications` (laravel default)
- `webhooks`, `webhook_deliveries`
- `personal_access_tokens` (sanctum)
- `consents`
- `password_history`

**Фаза 3:**
- `custom_field_definitions`, `custom_field_values`
- `approval_requests`, `approval_steps`
- `permission_groups`, `permission_group_permission`
- `auth_providers`, `auth_provider_users` (SSO)
- `saved_views`
- `audit_sinks`
- `subscriptions`, `plans`, `plan_features`, `usage_records` (Cashier + custom)
- `resource_permissions`

Изменения в существующих:
- `roles.parent_id` (inheritance)
- `permissions.is_wildcard`
- `users.locale`, `users.settings` (json)
- `tenants.settings` (расширить: branding, retention, require_2fa)
- `deals.lost_reason`, `deals.approval_status`
- `tasks.recurrence_rule`, `tasks.recurrence_parent_id`, `tasks.parent_id`

## Приложение C. Дополнительные feature flags

К имеющимся 4 (`advanced_analytics`, `audit_export`, `api_access`, `bulk_import`) добавить:

- `two_factor_required` — принудительный 2FA.
- `webhooks` — outgoing webhooks.
- `custom_fields` — EAV-кастомные поля.
- `approval_workflows` — двух-шаговые approvals.
- `sso` — SAML/OIDC.
- `kanban_board` — pipeline UI.
- `calendar_sync` — Google/Microsoft calendar.
- `pdf_reports` — PDF экспорт.
- `slack_integration` — Slack notifications.
- `web_to_lead` — публичная форма захвата.
- `bring_your_own_storage` — S3 в bucket тенанта.

Это позволит включать дорогие/тяжёлые фичи только тем тенантам, которые за них платят (привязка к плану — см. 10.4).
