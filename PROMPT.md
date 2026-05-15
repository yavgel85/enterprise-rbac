# Prompt: Build an Enterprise RBAC CRM (Laravel 13)

Use this document as a single self-contained brief to recreate the project from scratch. Follow every section literally. The implementation should be **deterministic**: same migrations, same enums, same seeder data, same route names, same test suite.

---

## 1. Mission statement

Build a multi-tenant CRM application called **Enterprise RBAC** with a hybrid RBAC authorization model (system + custom roles, plus direct user `grant`/`deny` permissions with TTL), full audit logging, per-tenant feature flags, and invite-only onboarding. Provide a Tailwind 4 Blade UI, comprehensive Pest 4 test suite, demo seed data covering all edge cases, and a `README.md` describing roles and demo credentials.

The result must satisfy **all** of the following:

- 5 system roles per tenant (`tenant-admin`, `manager`, `sales`, `auditor`, `viewer`) + a global `super-admin` flag.
- 42 atomic permissions described as a PHP enum `App\Enums\Permission`, formatted `module.action`.
- Path-based multi-tenancy `/t/{slug}/...`, global `TenantScope`, super-admin bypass.
- Permission resolution pipeline: super-admin → user active → tenant active → cross-tenant → role permissions + direct grants − direct denies.
- Privilege escalation prevention (`level` field) and separation-of-duties pairs.
- `deny` direct permission always overrides role grants.
- Context-aware policy rules (business hours for deal approval, draft-only deal updates, self-delete protection, etc.).
- Audit log for CUD events on auditable models + auth events + permission denials, with diff + IP/UA/URL.
- Feature-flag middleware `feature:slug` and per-tenant toggles.
- Invite flow with token + TTL + acceptance form.
- 56 passing Pest tests with **`LazilyRefreshDatabase`**.

---

## 2. Tech stack (lock to these versions)

| Layer | Choice |
|------|--------|
| PHP | `^8.3` |
| Framework | `laravel/framework:^13.7` |
| Tinker | `laravel/tinker:^3.0` |
| Testing | `pestphp/pest:^4.7`, `pestphp/pest-plugin-laravel:^4.1` |
| Code style | `laravel/pint:^1.27` |
| Mocking | `mockery/mockery:^1.6` |
| DB | SQLite (dev), file `database/database.sqlite` |
| Sessions/Queue/Cache | `database` driver |
| Frontend | Blade + Tailwind CSS `^4.0.0` via `@tailwindcss/vite` and `laravel-vite-plugin:^3.1`, Vite `^8.0.0` |
| Node | 20+ |

`composer.json` must export PSR-4 for `App\`, `Database\Factories\`, `Database\Seeders\`, `Tests\` and include a `setup` script that runs install, key:generate, migrate, npm install/build. Allow plugins `pestphp/pest-plugin`, `php-http/discovery`.

Vite config uses `bunny('Instrument Sans', { weights: [400,500,600] })`.

---

## 3. Repository layout

```
app/
├── Actions/{Audit,Authorization,Invitation,Tenant}/*.php
├── Authorization/
│   ├── Constraints/RoleAssignmentConstraint.php
│   ├── RoleDefinition.php
│   ├── RoleRegistry.php
│   └── TenantAuthorizer.php
├── Enums/{Permission,AuditAction,DealStage,DealStatus,CompanyStatus,TaskStatus,TaskPriority,ActivityType,DirectPermissionType}.php
├── Http/
│   ├── Controllers/{Auth,Admin,Crm,SuperAdmin}/*.php + DashboardController + Controller (base)
│   ├── Middleware/{ResolveTenant,CheckPermission,CheckFeature,EnsureSuperAdmin}.php
│   └── Requests/{Company,Contact,Deal,Task,Activity}Request.php
├── Listeners/{RecordSuccessfulLogin,RecordLogout,RecordFailedLogin}.php
├── Models/
│   ├── Concerns/{BelongsToTenant,Auditable,HasRoles,HasPermissions}.php
│   ├── Scopes/TenantScope.php
│   └── {Tenant,Department,Module,Permission,Role,User,AuditLog,Feature,Invitation,Company,Contact,Deal,Task,Activity}.php
├── Policies/{Activity,Company,Contact,Deal,Department,Invitation,Role,Task,Tenant,User}Policy.php
└── Providers/{AppServiceProvider,AuthServiceProvider}.php
bootstrap/{app.php,providers.php}
config/rbac.php
database/{factories,seeders,migrations}/...
resources/views/{auth,crm,admin,super-admin,partials,layouts}/*.blade.php
routes/web.php
tests/{Pest.php,TestCase.php,Feature,Unit}/*.php
PROMPT.md
README.md
```

All PHP files declare `declare(strict_types=1);`. Use `final readonly class` for Actions, Authorization service objects, RoleDefinition, listeners. Use thin `final readonly class` policies that delegate to `TenantAuthorizer`.

---

## 4. Configuration

### 4.1 `bootstrap/app.php`

Register middleware aliases:

```
tenant       => App\Http\Middleware\ResolveTenant
permission   => App\Http\Middleware\CheckPermission
feature      => App\Http\Middleware\CheckFeature
super-admin  => App\Http\Middleware\EnsureSuperAdmin
```

Routing: web from `routes/web.php`, commands from `routes/console.php`, health endpoint `/up`.

### 4.2 `bootstrap/providers.php`

```php
return [App\Providers\AppServiceProvider::class, App\Providers\AuthServiceProvider::class];
```

### 4.3 `config/rbac.php`

```php
return [
    'cache_ttl' => env('RBAC_CACHE_TTL', 3600),
    'forbidden_role_pairs' => [
        ['auditor', 'tenant-admin'],
        ['auditor', 'manager'],
    ],
    'business_hours' => [
        'start' => env('RBAC_BUSINESS_HOURS_START', 9),
        'end' => env('RBAC_BUSINESS_HOURS_END', 18),
        'weekdays_only' => env('RBAC_BUSINESS_HOURS_WEEKDAYS_ONLY', true),
    ],
    'invitation_ttl_days' => env('RBAC_INVITATION_TTL_DAYS', 7),
];
```

### 4.4 `.env` overrides

```
APP_NAME="Enterprise RBAC"
DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log
```

---

## 5. Database schema (migrations in this exact order)

Create 18 migrations with sequential timestamps starting `2026_05_08_120000_...`. Keep the default `0001_01_01_000000_create_users_table.php`, `0001_01_01_000001_create_cache_table.php`, `0001_01_01_000002_create_jobs_table.php`.

### 5.1 `120000_create_tenants_table`

```php
Schema::create('tenants', function (Blueprint $t) {
    $t->id();
    $t->string('name');
    $t->string('slug')->unique();
    $t->string('domain')->nullable()->unique();
    $t->boolean('is_active')->default(true)->index();
    $t->json('settings')->nullable();
    $t->timestamps();
    $t->softDeletes();
});
```

### 5.2 `120001_create_departments_table`

```
id, tenant_id (cascade), parent_id (self, nullable, nullOnDelete),
name, slug, timestamps; unique(tenant_id, slug)
```

### 5.3 `120002_add_rbac_fields_to_users_table` (alter `users`)

```
tenant_id (nullable, cascadeOnDelete) after id,
department_id (nullable, nullOnDelete) after tenant_id,
is_super_admin boolean default false after password,
is_active boolean default true after is_super_admin,
last_login_at timestamp nullable,
last_login_ip varchar(45) nullable,
softDeletes,
index(tenant_id, is_active)
```

### 5.4 `120003_create_modules_table`

```
id, name, slug unique, description nullable, icon nullable,
sort_order unsignedInt default 0 index, timestamps
```

### 5.5 `120004_create_permissions_table`

```
id, module_id (cascadeOnDelete), name, slug unique,
description nullable, timestamps; index(module_id)
```

### 5.6 `120005_create_roles_table`

```
id, tenant_id nullable (cascade), name, slug,
description nullable, is_system bool default false,
level unsignedInt default 0, timestamps, softDeletes;
unique(tenant_id, slug), index(tenant_id, level)
```

### 5.7 `120006_create_role_user_table`

```
id, user_id (cascade), role_id (cascade),
assigned_by nullable (users.nullOnDelete),
assigned_at timestamp useCurrent,
expires_at timestamp nullable index,
timestamps; unique(user_id, role_id)
```

### 5.8 `120007_create_permission_role_table`

```
role_id (cascade), permission_id (cascade),
created_at nullable, primary(role_id, permission_id)  -- no id, composite PK
```

### 5.9 `120008_create_permission_user_table`

```
id, user_id (cascade), permission_id (cascade),
type enum('grant','deny') default 'grant',
expires_at nullable index,
assigned_by nullable, reason string nullable, timestamps;
unique(user_id, permission_id)
```

### 5.10 `120009_create_audit_logs_table`

```
id, tenant_id nullable (nullOnDelete), user_id nullable (nullOnDelete),
action string, nullableMorphs('auditable'),
old_values json nullable, new_values json nullable,
ip_address varchar(45), user_agent text, url string,
metadata json nullable, created_at useCurrent;
indexes: (tenant_id, created_at), (user_id, created_at), (action, created_at)
```

> `AuditLog` model uses `public $timestamps = false;` and explicitly sets `created_at`.

### 5.11 `120010_create_features_table`

```
id, name, slug unique, description nullable,
default_enabled boolean default false, timestamps
```

### 5.12 `120011_create_feature_tenant_table`

```
id, feature_id (cascade), tenant_id (cascade),
is_enabled bool default true, expires_at nullable, timestamps;
unique(feature_id, tenant_id)
```

### 5.13 `120012_create_invitations_table`

```
id, tenant_id (cascade), email,
token varchar(64) unique, role_id nullable (nullOnDelete),
department_id nullable (nullOnDelete), invited_by nullable,
expires_at timestamp, accepted_at nullable,
timestamps; index(tenant_id, email)
```

### 5.14 `120013_create_companies_table`

```
id, tenant_id (cascade), name, industry/website/phone/email nullable,
address text nullable, notes text nullable,
owner_id nullable, created_by nullable,
status enum('lead','active','inactive') default 'lead',
timestamps, softDeletes;
indexes: (tenant_id, status), (tenant_id, owner_id)
```

### 5.15 `120014_create_contacts_table`

```
id, tenant_id (cascade), company_id nullable (nullOnDelete),
first_name, last_name nullable, email/phone/position nullable,
owner_id/created_by nullable, timestamps, softDeletes;
indexes: (tenant_id, company_id), (tenant_id, owner_id)
```

### 5.16 `120015_create_deals_table`

```
id, tenant_id (cascade), company_id nullable, contact_id nullable,
department_id nullable, title string, amount decimal(15,2) default 0,
currency char(3) default 'USD',
stage enum('lead','qualified','proposal','negotiation','won','lost') default 'lead',
probability unsignedTinyInt default 0,
expected_close_date date nullable, closed_at timestamp nullable,
owner_id/created_by nullable,
status enum('draft','active','closed') default 'draft',
timestamps, softDeletes;
indexes: (tenant_id, stage), (tenant_id, owner_id), (tenant_id, status)
```

### 5.17 `120016_create_tasks_table`

```
id, tenant_id (cascade), nullableMorphs('taskable'),
title, description text nullable, due_date timestamp nullable,
priority enum('low','normal','high','urgent') default 'normal',
status enum('open','in_progress','done','cancelled') default 'open',
assignee_id nullable, created_by nullable, completed_at nullable,
timestamps, softDeletes;
indexes: (tenant_id, status), (tenant_id, assignee_id)
```

### 5.18 `120017_create_activities_table`

```
id, tenant_id (cascade),
type enum('call','meeting','email','note'),
subject, body text nullable, nullableMorphs('subjectable'),
happened_at nullable, user_id nullable, timestamps;
indexes: (tenant_id, type), (tenant_id, user_id)
```

---

## 6. Enums (`app/Enums/`)

All are backed string enums.

### 6.1 `Permission` (42 cases, format `module.action`)

```
users.view, users.create, users.update, users.delete, users.invite,
roles.view, roles.create, roles.update, roles.delete, roles.assign,
permissions.view, permissions.assign,
departments.view, departments.create, departments.update, departments.delete,
companies.view, companies.create, companies.update, companies.delete,
contacts.view, contacts.create, contacts.update, contacts.delete,
deals.view, deals.create, deals.update, deals.delete, deals.approve, deals.export,
tasks.view, tasks.create, tasks.update, tasks.delete, tasks.complete,
activities.view, activities.create, activities.update, activities.delete,
audit.view, audit.export,
features.view
```

Helper methods:

```php
public function module(): string  // explode('.', value)[0]
public function action(): string  // explode('.', value)[1]
public function label(): string   // "Action Module"
public static function groupedByModule(): array<string, list<self>>
```

### 6.2 `AuditAction`

```
created, updated, deleted, restored,
login, logout, login_failed,
permission_denied, roles_assigned, role_revoked,
permission_granted, permission_revoked,
invitation_sent, invitation_accepted,
tenant_bootstrapped, tenant_suspended, tenant_activated,
deal_approved, task_completed
```

### 6.3 Other enums

- `DealStage`: lead, qualified, proposal, negotiation, won, lost (+ `isClosed()` returns true for `won`/`lost`)
- `DealStatus`: draft, active, closed
- `CompanyStatus`: lead, active, inactive
- `TaskStatus`: open, in_progress, done, cancelled (label "In progress" for in_progress)
- `TaskPriority`: low, normal, high, urgent
- `ActivityType`: call, meeting, email, note
- `DirectPermissionType`: grant, deny

All enums except `DirectPermissionType` expose `label(): string` (ucfirst by default).

---

## 7. Models

### 7.1 Traits / Scopes

**`App\Models\Scopes\TenantScope`** implements `Scope::apply`:

1. If `Auth::user()->is_super_admin` → return (no filter).
2. `$tenantId = Context::get('tenant_id') ?? auth()->user()?->tenant_id;`
3. If null → no filter.
4. Otherwise `$builder->where($model->qualifyColumn('tenant_id'), $tenantId)`.

**`App\Models\Concerns\BelongsToTenant`**:

- `bootBelongsToTenant`: add global `TenantScope`. `creating` hook: if `!tenant_id`, set from `Context::get('tenant_id') ?? auth()->user()?->tenant_id`.
- `tenant(): BelongsTo` to `Tenant`.

**`App\Models\Concerns\Auditable`**:

- `bootAuditable`: hook `created/updated/deleted/restored` and call `app(LogAuditEvent::class)->forModel(AuditAction::X, $model)`.

**`App\Models\Concerns\HasRoles`** (on User):

```php
roles(): BelongsToMany   // pivot: assigned_by, assigned_at, expires_at + timestamps
activeRoles(): BelongsToMany // role_user.expires_at NULL OR > now()
hasRole(string): bool
hasAnyRole(array): bool
maxRoleLevel(): int  // max active roles.level
```

**`App\Models\Concerns\HasPermissions`** (on User):

```php
directPermissions(): BelongsToMany // table permission_user, pivot type/expires_at/assigned_by/reason
hasPermission(PermissionEnum|string): bool // super-admin shortcut → always true; else lookup ResolveUserPermissions
allPermissions(): array<string, true> // same logic
```

### 7.2 Specific models

- **Tenant**: `HasFactory, SoftDeletes`; fillable `name, slug, domain, is_active, settings`; cast `is_active=>bool, settings=>array`; default `is_active=true`; `getRouteKeyName()` returns `'slug'`; relations `users`, `departments`, `roles`, `invitations`, `features(): BelongsToMany withPivot(is_enabled, expires_at) withTimestamps()`; method `hasFeature(string): bool` that checks `is_enabled=true AND expires_at NULL OR > now()`.
- **Department**: `BelongsToTenant`; fillable `tenant_id, parent_id, name, slug`; self relations `parent`, `children`; `users(): HasMany`.
- **Module**: fillable `name, slug, description, icon, sort_order`; cast `sort_order=>int`; `permissions(): HasMany`.
- **Permission**: fillable `module_id, name, slug, description`; relations `module(): BelongsTo`, `roles(): BelongsToMany`, `users(): BelongsToMany` via table `permission_user` with pivot `type, expires_at, assigned_by, reason` + timestamps.
- **Role**: `HasFactory, SoftDeletes`; fillable `tenant_id, name, slug, description, is_system, level`; defaults `is_system=false, level=0`; cast `is_system=>bool, level=>int`; `tenant(): BelongsTo`, `permissions(): BelongsToMany`, `users(): BelongsToMany withPivot(assigned_by, assigned_at, expires_at) withTimestamps`.
- **User** extends `Authenticatable`, uses `HasFactory, HasPermissions, HasRoles, Notifiable, SoftDeletes`. Fillable `tenant_id, department_id, name, email, password, is_super_admin, is_active, last_login_at, last_login_ip`. Hidden `password, remember_token`. Defaults `is_super_admin=false, is_active=true`. Casts `email_verified_at=>datetime, password=>hashed, is_super_admin=>bool, is_active=>bool, last_login_at=>datetime`. Relations `tenant()`, `department()`.
- **AuditLog**: `$timestamps = false`; fillable list above + `created_at`; casts `old_values/new_values/metadata => array, created_at => datetime`; relations `tenant, user, auditable (MorphTo)`.
- **Feature**: fillable `name, slug, description, default_enabled`; default `default_enabled=false`; `tenants(): BelongsToMany withPivot(is_enabled, expires_at) withTimestamps`.
- **Invitation**: `BelongsToTenant`; fillable `tenant_id, email, token, role_id, department_id, invited_by, expires_at, accepted_at`; casts dates; relations `role, department, inviter (invited_by)`; helpers `isExpired()`, `isAccepted()`, `isPending()`.
- **Company**: `Auditable, BelongsToTenant, SoftDeletes`; fillable plus `status`; default `status='lead'`; cast `status => CompanyStatus`; relations `owner, creator, contacts, deals`.
- **Contact**: `Auditable, BelongsToTenant, SoftDeletes`; method `fullName(): string`; relations `company, owner, creator`.
- **Deal**: `Auditable, BelongsToTenant, SoftDeletes`; fillable list above; defaults `amount=0, currency='USD', stage='lead', probability=0, status='draft'`; casts `amount=>decimal:2, probability=>int, stage=>DealStage, status=>DealStatus, expected_close_date=>date, closed_at=>datetime`; relations + `tasks(): MorphMany(Task,'taskable')`, `activities(): MorphMany(Activity,'subjectable')`.
- **Task**: `Auditable, BelongsToTenant, SoftDeletes`; fillable list above; defaults priority/status; casts priority/status enums + dates; `taskable(): MorphTo`, `assignee, creator`.
- **Activity**: `Auditable, BelongsToTenant` (no SoftDeletes); casts `type=>ActivityType, happened_at=>datetime`; `subjectable(): MorphTo`, `user`.

---

## 8. Authorization layer

### 8.1 `App\Authorization\RoleDefinition`

`final readonly class` with `slug, name, level, permissions (list<Permission>), description`. Methods `has(Permission)` and `permissionSlugs(): list<string>`.

### 8.2 `App\Authorization\RoleRegistry::all(): array<string, RoleDefinition>`

Define 5 roles:

| slug | level | description | permissions |
|------|-------|-------------|-------------|
| `tenant-admin` | 90 | Full access within the tenant | `Permission::cases()` (all 42) |
| `manager` | 70 | CRM full access + audit visibility | users.view, departments.view, companies.{view,create,update}, contacts.{view,create,update}, deals.{view,create,update,approve}, tasks.{view,create,update,complete}, activities.{view,create,update}, audit.view |
| `sales` | 40 | CRM operations without approve or delete | companies.{view,create,update}, contacts.{view,create,update}, deals.{view,create,update}, tasks.{view,create,update,complete}, activities.{view,create,update} |
| `auditor` | 30 | Read-only access plus full audit log | companies.view, contacts.view, deals.view, tasks.view, activities.view, audit.view, audit.export |
| `viewer` | 10 | Read-only access to CRM data | companies.view, contacts.view, deals.view, tasks.view, activities.view |

Also expose `get(string $slug): RoleDefinition` (throws `InvalidArgumentException`) and `slugs(): list<string>`.

### 8.3 `App\Authorization\Constraints\RoleAssignmentConstraint`

`forbiddenPairs()` reads `config('rbac.forbidden_role_pairs', [])`. `assertValid(list<string> $slugs)` throws `DomainException` if both members of any forbidden pair are present (order-insensitive).

### 8.4 `App\Authorization\TenantAuthorizer`

`final readonly class` with `__construct(private ResolveUserPermissions $resolve)`. Single method:

```php
allows(User $user, Permission $permission, ?Tenant $tenant = null, ?Model $resource = null): Response
```

Order of checks (all return `Response::deny('reason')` early; otherwise `Response::allow()`):

1. `$user->is_super_admin` → allow.
2. `! $user->is_active` → "User account is inactive."
3. `$tenant ??= $user->tenant`. If null or `!is_active` → "Tenant is inactive or missing."
4. If `$resource` has `tenant_id` and it ≠ `$tenant->id` → "Cross-tenant access is forbidden."
5. `$permissions = $this->resolve->handle($user);` if `!isset($permissions[$permission->value])` → "Missing permission: {slug}". Otherwise allow.

### 8.5 `App\Providers\AuthServiceProvider`

Register all 10 policies (`Activity, Company, Contact, Deal, Department, Invitation, Role, Task, Tenant, User`) and:

```php
Gate::before(fn (User $user) => $user->is_super_admin ? true : null);
```

### 8.6 Policies

All return `Illuminate\Auth\Access\Response`. Each policy is a `final readonly class` injecting `TenantAuthorizer`. Methods just delegate (`$this->auth->allows($user, Permission::X, resource: $model)`), with these exceptions:

- **`UserPolicy::delete`** – after permission check, deny if `$user->id === $target->id` ("You cannot delete your own account.").
- **`UserPolicy::invite`** uses `Permission::UsersInvite`.
- **`RolePolicy::update/delete`** – after permission check, deny if `$role->is_system` ("System roles cannot be edited/deleted.").
- **`RolePolicy::syncPermissions`** – uses `Permission::PermissionsAssign`; deny if `is_system` ("Permissions on system roles can only be edited by super admins.").
- **`DealPolicy::update`** – after permission check: deny if `status !== DealStatus::Draft`; then require `owner_id === user.id || department_id !== null && department_id === user.department_id`.
- **`DealPolicy::approve`** – after permission check: deny if `weekdays_only && !isWeekday()`; deny if `now().hour < start || >= end`.
- **`DealPolicy::export`** uses `Permission::DealsExport`.
- **`TaskPolicy::complete`** – after permission check: allow only if `assignee_id === user.id || created_by === user.id`.
- **`InvitationPolicy::{viewAny,create,delete}`** all use `Permission::UsersInvite`.
- **`TenantPolicy`** does **not** inject the authorizer; `viewAny`/`manage` simply check `$user->is_super_admin`.

Standard CRUD methods: `viewAny, view, create, update, delete`. Add specialized methods where listed above.

---

## 9. Actions (single-responsibility, `final readonly class`)

### 9.1 `App\Actions\Audit\LogAuditEvent`

Two public methods (no constructor):

- `forModel(AuditAction $action, Model $model, array $metadata = []): void` – guards against logging `AuditLog` itself; resolves `tenant_id` from model → `Context::get('tenant_id')` → `Auth::user()?->tenant_id`. Captures:
  - `Updated`: `getChanges()` minus `updated_at`; old = `getOriginal()` per changed key.
  - `Created`/`Restored`: `new_values = $model->getAttributes()`.
  - `Deleted`: `old_values = $model->getOriginal()`.
- `handle(AuditAction $action, ?Model $subject = null, array $metadata = []): void` – for non-model events. Captures `tenant_id` from Context, `user_id` from `Auth::id()`, `auditable_type = $subject?->getMorphClass()`, `auditable_id = $subject?->getKey()`.

Both include `ip_address`, `user_agent`, `url`, `created_at = now()`.

### 9.2 `App\Actions\Authorization\ResolveUserPermissions`

Inject `Illuminate\Contracts\Cache\Repository`.

```php
handle(User $user): array<string, true>
```

- Super-admin → map all `PermissionEnum::cases()` to true.
- Otherwise `$this->cache->remember(cacheKey($user), config('rbac.cache_ttl', 3600), fn() => $this->resolve($user))`.

`cacheKey(User): string` returns `"rbac:tenant:{tenant_id ?? null}:user:{id}:permissions"` (static method).

`resolve(User)` (protected):

```
$rolePerms = user->roles()
    ->where(fn $q -> $q->whereNull('role_user.expires_at')->orWhere('role_user.expires_at','>',$now))
    ->with('permissions:id,slug')->get()->flatMap->permissions->pluck('slug');

$grants = user->directPermissions()->wherePivot('type','grant')
    ->where(fn $q -> ... expires_at IS NULL OR > now)
    ->pluck('slug');

$denies = user->directPermissions()->wherePivot('type','deny')
    ->where(fn $q -> ... expires_at IS NULL OR > now)->pluck('slug')->all();

return rolePerms->merge($grants)->unique()
    ->reject(fn $s -> in_array($s,$denies))
    ->mapWithKeys(fn $s -> [$s => true])->all();
```

### 9.3 `App\Actions\Authorization\ForgetUserPermissionsCache`

Inject the cache repository. Methods `forUser(User)` and `forRole(Role)` (uses `$role->users()->select(['users.id','users.tenant_id'])->lazyById()->each(fn $u -> cache->forget(cacheKey($u)))`).

### 9.4 `App\Actions\Authorization\AssignRolesToUser`

Inject `RoleAssignmentConstraint`, `ForgetUserPermissionsCache`, `LogAuditEvent`. `handle(User $actor, User $member, list<int> $roleIds, ?DateTimeInterface $expiresAt = null): void` inside `DB::transaction`:

1. If `$member->tenant_id === null && !$actor->is_super_admin` → throw.
2. Fetch roles `withoutGlobalScopes` whose id in list AND (`tenant_id = member.tenant_id` OR (`tenant_id NULL AND is_system`)). If count mismatch → throw "One or more roles are not available for this tenant."
3. `$constraint->assertValid(roleSlugs)`.
4. If not super-admin: `actorMaxLevel = actor->maxRoleLevel()`; `if max(roles.level) >= actorMaxLevel` → throw "You cannot assign a role equal to or higher than your own."
5. `member->roles()->sync([id => ['assigned_by'=>actor.id,'assigned_at'=>now(),'expires_at'=>$expiresAt]])`.
6. Forget cache.
7. Audit `RolesAssigned` with metadata `{role_ids, role_slugs, expires_at}`.

### 9.5 `RevokeRoleFromUser`

`handle(actor, member, Role)`: detach role, forget cache, audit `RoleRevoked` with `{role_id, role_slug, actor_id}`.

### 9.6 `GrantDirectPermission`

`handle(actor, member, Permission, DirectPermissionType = Grant, ?DateTimeInterface, ?string $reason)`. If `!actor->is_super_admin && !actor->hasPermission($permission->slug)` → throw "You cannot grant a permission that you do not hold yourself." Inside transaction:

1. `syncWithoutDetaching` with pivot `[type,expires_at,assigned_by,reason,created_at,updated_at]`.
2. `updateExistingPivot` to ensure attributes overwrite if the row pre-existed.
3. Forget cache. Audit `PermissionGranted` with metadata.

### 9.7 `RevokeDirectPermission`

Detach permission, forget cache, audit `PermissionRevoked`.

### 9.8 `CreateTenantRole`

`handle(actor, Tenant, array $attrs)`: if not super-admin and `level >= actor->maxRoleLevel()` → throw. Create role with `tenant_id, name, slug (Str::slug fallback), description, level, is_system=false`. No audit needed.

### 9.9 `UpdateTenantRole`

Inject `ForgetUserPermissionsCache`. If `role->is_system` → throw "System roles cannot be modified." If actor non-super and `attrs.level >= actor->maxRoleLevel()` → throw. Fill allowed keys (`name, slug, description, level`), save, forget cache for the role.

### 9.10 `DeleteTenantRole`

If `is_system` → throw. If `users()->exists()` → throw "Cannot delete a role that is still assigned to users. Reassign them first." Forget cache then `$role->delete()`.

### 9.11 `SyncRolePermissions`

`handle(actor, Role, list<string> $permissionSlugs)`. If `role->is_system && !actor->is_super_admin` → throw. Validate slugs against enum; throw if unknown. If actor non-super, ensure actor holds every requested slug (else throw with missing list). Inside transaction: `$role->permissions()->sync($idsBySlug);` then forget cache.

### 9.12 `App\Actions\Tenant\BootstrapTenant`

Inject `LogAuditEvent`. Inside transaction:

1. Pluck all permissions `[slug => id]`.
2. For each `RoleRegistry::all()`: create role (`tenant_id, name, slug, description, level, is_system=false`) and `sync` matching permission ids.
3. `Department::firstOrCreate(['tenant_id'=>id,'slug'=>'general'], ['name'=>'General'])`.
4. If `$firstAdmin` provided: set its `tenant_id`, attach `tenant-admin` role with `assigned_at=now()`.
5. Audit `TenantBootstrapped`.

### 9.13 `App\Actions\Invitation\InviteUser`

`handle(actor, Tenant, email, ?Role, ?int $departmentId): Invitation`. Privilege escalation block (role level ≥ actor's). Tenant-mismatch block (`role->tenant_id !== null && !== target tenant`). Existing-email check (`User::withoutGlobalScopes()->where(tenant_id,email)->exists()`) → throw. Compute `expires_at = now()->addDays(config('rbac.invitation_ttl_days',7))`. Create invitation with `token = Str::random(48)`. Audit `InvitationSent` with `{email, role_slug, expires_at}`.

### 9.14 `App\Actions\Invitation\AcceptInvitation`

`handle(Invitation, ['name','password'])`: if `!isPending()` → throw. Inside transaction: create user (`is_active=true, email_verified_at=now()`, hash password), attach role if invitation has one, set `accepted_at=now()`, audit `InvitationAccepted`.

---

## 10. Middleware

- **`ResolveTenant`**: read `$tenant = $request->route('tenant')`. If not a `Tenant`, pass through. Else `abort(403)` if `!is_active`; `abort(403)` if user is logged in, not super-admin, and `tenant_id !== tenant.id`. Then `Context::add('tenant_id', $tenant->id)` and `app()->instance('current_tenant', $tenant)`.
- **`CheckPermission`** (`permission:slug`): inject `ResolveUserPermissions` and `LogAuditEvent`. If unauth → 401. Super-admin → pass. If `!isset(perms[$slug])` → audit `PermissionDenied` with `{permission, route}`, then `abort(403, "Missing permission: {slug}")`.
- **`CheckFeature`** (`feature:slug`): fetch `app('current_tenant')`; if missing or `!hasFeature($slug)` → abort 403.
- **`EnsureSuperAdmin`**: abort 403 if user missing or `!is_super_admin`.

---

## 11. Routes (`routes/web.php`)

Root `/` (named `home`): if authed super-admin → redirect to `super-admin.tenants.index`; if has tenant → `tenant.dashboard`; else `/login`.

Guest group:

```
GET  /login                       login (LoginController@show)
POST /login                       throttle:6,1 (store)
GET  /invitations/{token}         invitation.show
POST /invitations/{token}         invitation.accept
```

Auth group (`auth`):

```
POST /logout                      logout
```

Super-admin prefix (`super-admin/`, name `super-admin.`, middleware `super-admin`):

```
GET  tenants                      super-admin.tenants.index
GET  tenants/create               .create
POST tenants                      .store
GET  tenants/{tenant}             .show
PUT  tenants/{tenant}/toggle      .toggle
PUT  tenants/{tenant}/features/{feature}  .features.toggle
GET  permissions                  super-admin.permissions.index
GET  audit                        super-admin.audit.index
```

Tenant prefix (`t/{tenant}`, middleware `tenant`):

- `GET /` named `tenant.dashboard` → `DashboardController@show`.
- Admin subprefix `admin/`, name `admin.`:
  - `GET users` `.users.index`
  - `POST users/invite` `.users.invite`
  - `GET users/{user}` `.users.show`
  - `PUT users/{user}/roles` `.users.roles.sync`
  - `GET roles` `.roles.index`
  - `GET roles/create` `.roles.create`
  - `POST roles` `.roles.store`
  - `GET roles/{role}/edit` `.roles.edit`
  - `PUT roles/{role}` `.roles.update`
  - `PUT roles/{role}/permissions` `.roles.permissions.sync`
  - `DELETE roles/{role}` `.roles.destroy`
  - `GET permissions` `.permissions.index`
  - `GET permissions/users/{user}` `.permissions.user.edit`
  - `POST permissions/users/{user}` `.permissions.user.grant`
  - `DELETE permissions/users/{user}/{permission}` `.permissions.user.revoke`
  - `GET departments` `.departments.index`
  - `POST departments` `.departments.store`
  - `PUT departments/{department}` `.departments.update`
  - `DELETE departments/{department}` `.departments.destroy`
  - `GET audit` `.audit.index`
  - `POST audit/export` `.audit.export`
- CRM subprefix `crm/`, name `crm.`:
  - `Route::resource('companies', CompanyController::class)` (7 routes)
  - `Route::resource('contacts', ContactController::class)`
  - `Route::resource('deals', DealController::class)` + `POST deals/{deal}/approve` `.deals.approve`
  - `Route::resource('tasks', TaskController::class)` + `POST tasks/{task}/complete` `.tasks.complete`
  - `GET activities` `.activities.index`, `GET activities/create`, `POST activities`, `DELETE activities/{activity}` (no edit/show/update)

Total = **74 routes** including resource expansions.

Use `Tenant::getRouteKeyName()='slug'` so URLs read `/t/acme/...`.

---

## 12. Controllers

Base `app/Http/Controllers/Controller.php` is `abstract class` using `AuthorizesRequests, ValidatesRequests`.

### 12.1 Auth

- **`LoginController::show()`** → `view('auth.login')`.
- **`LoginController::store(Request)`**: validate `email/password`. `Auth::attempt(..., $remember)` → on fail throw `ValidationException::withMessages(['email' => ...])`. Regenerate session. If `!user->is_active` → logout + error. Redirect: super-admin → `super-admin.tenants.index`; else `tenant.dashboard` with user's tenant; else `/`.
- **`LoginController::destroy()`**: `Auth::logout`, invalidate session, regenerate token, redirect `login`.
- **`InvitationController::show(string $token)`**: fetch invitation by token, `abort_if(!isPending(),410)`, return form.
- **`InvitationController::accept(Request, AcceptInvitation $accept, string $token)`**: validate `name, password (min:8 confirmed)`, run action, `Auth::login($user)`, redirect to `tenant.dashboard`.

### 12.2 Dashboard

- **`DashboardController::show(Tenant)`**: compute counts (companies/contacts/deals/activities/users), recent 5 deals with company+owner, return `view('dashboard',...)`.

### 12.3 Admin

- **`UserController`**: `index` paginates users (tenant scope) with `roles, department`; lists pending invitations. `show` loads roles/directPermissions/department + all tenant roles ordered by level desc. `syncRoles(AssignRolesToUser)` validates role_ids[], catches `DomainException` → flash. `invite(InviteUser)` validates email/role_id/department_id, catches `DomainException`.
- **`RoleController`**: `index` (withCount users/permissions, orderByDesc level); `create/store(CreateTenantRole)`; `edit` loads permissions and supplies `permissionsByModule = PermissionEnum::groupedByModule()` plus `rolePermissionSlugs`; `update(UpdateTenantRole)`; `syncPermissions(SyncRolePermissions)`; `destroy(DeleteTenantRole)`. All catch `DomainException`.
- **`PermissionController`**: `index` lists all permissions grouped by module name. `userEdit(Tenant, User)` provides allPermissions + directMap. `userGrant(GrantDirectPermission)` validates `permission_id, type:grant|deny, expires_at, reason`. `userRevoke(RevokeDirectPermission)` route-bound permission.
- **`DepartmentController`**: `index` (withCount users), `store` (Str::slug + random suffix), `update`, `destroy`.
- **`AuditController`**: `index` filters by `action`, paginates 50, lists distinct actions per tenant. `export` requires `audit.export` permission, requires feature `audit_export` (flash error if disabled), streams CSV with `id, action, user_email, auditable, ip, created_at` via `lazyById`.

### 12.4 CRM

All CRM controllers call `$this->authorize('action', $modelOrClass)`. They set `created_by = $request->user()->id` on store. Return `view(...)` with `compact` data.

- **`CompanyController`**: standard 7 actions; `index` paginates 20 with owner; show eager loads owner/creator/contacts/deals.
- **`ContactController`**: standard; helper `users(Tenant)` for select options.
- **`DealController`**: standard + `approve(Tenant, Deal, LogAuditEvent)` which after policy check sets `stage=Won, status=Closed, closed_at=now()` and logs `DealApproved`.
- **`TaskController`**: standard + `complete(...)` which sets `status=Done, completed_at=now()` and logs `TaskCompleted`.
- **`ActivityController`**: `index`, `create`, `store` (sets `user_id = request->user()->id`), `destroy`.

### 12.5 SuperAdmin

- **`TenantController`**:
  - `index`: paginate tenants `withTrashed()` with counts of `users, departments, roles`.
  - `create/store(BootstrapTenant)`: validate `name, slug unique, is_active`; create tenant; bootstrap; redirect to show.
  - `show`: load features; list `allFeatures`.
  - `toggle`: flip `is_active`.
  - `toggleFeature(Tenant, Feature)`: `$tenant->features()->syncWithoutDetaching([id => ['is_enabled' => bool]])`.
- **`PermissionController::index`**: list modules with permissions (ordered by sort_order then slug).
- **`PermissionController::show(Permission)`** (route optional): load module + counts.
- **`AuditController::index`**: paginate 50 with tenant/user, filterable by `tenant_id` and `action`.

---

## 13. Form Requests

All FormRequest classes return `authorize() => true` (policy enforcement happens in controllers) and define `rules()`:

- **`CompanyRequest`**: name required string max 255; industry/phone/email/address/notes nullable; website nullable url; status required Rule::enum CompanyStatus; owner_id nullable exists.
- **`ContactRequest`**: first_name required; last_name/email/phone/position nullable; company_id nullable exists; owner_id nullable exists.
- **`DealRequest`**: title required; amount required numeric min 0; currency required size 3; stage Rule::enum DealStage; status Rule::enum DealStatus; probability required integer 0-100; expected_close_date nullable date; company_id/contact_id/department_id/owner_id nullable exists.
- **`TaskRequest`**: title required; description nullable; due_date nullable; priority/status enums; assignee_id nullable exists.
- **`ActivityRequest`**: type Rule::enum ActivityType; subject required; body nullable; happened_at nullable.

---

## 14. Listeners + AppServiceProvider

`App\Providers\AppServiceProvider::boot()` registers Event listeners:

```php
Event::listen(Login::class, RecordSuccessfulLogin::class);
Event::listen(Logout::class, RecordLogout::class);
Event::listen(Failed::class, RecordFailedLogin::class);
```

Listeners (`final readonly class`, inject `LogAuditEvent`):

- `RecordSuccessfulLogin`: on `Illuminate\Auth\Events\Login`, if user is `App\Models\User` then `forceFill(['last_login_at'=>now(),'last_login_ip'=>request()->ip()])->saveQuietly()`. Audit `Login`.
- `RecordLogout`: audit `Logout`.
- `RecordFailedLogin`: audit `LoginFailed` with metadata `['email' => credentials.email]`.

---

## 15. Factories

Provide factories for all models. Conventions:

- `UserFactory`: defaults `tenant_id=null, department_id=null, is_super_admin=false, is_active=true, password=Hash::make('password')`. States `unverified()`, `superAdmin()` (`is_super_admin=true, tenant_id=null`), `inactive()` (`is_active=false`).
- `TenantFactory`: random company + numeric suffix in slug; state `inactive()`.
- `RoleFactory`: tenant via factory; level 10..80; state `system()` (`tenant_id=null, is_system=true`), `withLevel(int)`.
- `DepartmentFactory`: random name + slug suffix.
- `ModuleFactory`, `PermissionFactory`, `FeatureFactory`: random with unique slugs.
- `InvitationFactory`: token Str::random(48); states `expired()` (`expires_at=now()->subDay()`), `accepted()` (`accepted_at=now()`).
- `CompanyFactory`, `ContactFactory`, `DealFactory`, `TaskFactory`, `ActivityFactory`: tenant via factory, plausible faker data, sensible defaults; `DealFactory` states `active()`, `won()` (`stage=won, status=closed, closed_at=now()`); `TaskFactory` state `done()`.

---

## 16. Seeders

`DatabaseSeeder` runs in order: `ModuleSeeder, PermissionSeeder, FeatureSeeder, SuperAdminUserSeeder, DemoTenantSeeder`. Use `WithoutModelEvents`.

### 16.1 `ModuleSeeder`

Insert (via `updateOrCreate` on `slug`) 12 modules: `system, users, roles, permissions, departments, companies, contacts, deals, tasks, activities, audit, features` with names, descriptions, sort_order 0/10/20/.../110.

### 16.2 `PermissionSeeder`

For each `Permission::cases()`: derive `moduleSlug` from `->module()`, look up module id, `updateOrCreate(['slug'=>case->value], ['module_id','name'=>label,'description'=>null])`.

### 16.3 `FeatureSeeder`

Insert 4 features: `advanced_analytics, audit_export, api_access, bulk_import`, all `default_enabled=false`.

### 16.4 `SuperAdminUserSeeder`

`updateOrCreate({email:'super@enterprise-rbac.test'}, {name:'Super Admin', password:Hash::make('password'), tenant_id:null, is_super_admin:true, is_active:true, email_verified_at:now()})`.

### 16.5 `DemoTenantSeeder`

Inject `BootstrapTenant`. Run two methods:

**Acme** (`slug='acme', name='Acme Corp'`):
- Bootstrap.
- Departments `Sales, Support, Finance`.
- Enable features `advanced_analytics`, `audit_export`.
- Users (all password=`password`): `admin@acme.test` (Acme Admin, dept Sales), `manager@acme.test` (Mary Manager, Sales), `sales@acme.test` (Sam Sales, Sales), `auditor@acme.test` (Alice Auditor, no dept), `viewer@acme.test` (Vince Viewer, no dept), `multi@acme.test` (Mia Multi-Role, Sales), `temp@acme.test` (Tom Temporary, Sales).
- Attach roles: admin→tenant-admin; manager→manager; sales→sales; auditor→auditor; viewer→viewer; multi→manager+sales; temp→sales with `expires_at = now()+7 days`.
- Seed CRM data.

**Globex** (`slug='globex', name='Globex Inc'`):
- Bootstrap.
- Departments `Sales, Support, Finance`.
- Enable `audit_export` only.
- Users: `admin@globex.test`, `manager@globex.test`(Support), `sales@globex.test`(Sales), `auditor@globex.test`, `viewer@globex.test`, `granted@globex.test`(Sales), `denied@globex.test`(Sales).
- Roles: admin→tenant-admin; manager→manager; sales→sales; auditor→auditor; viewer→viewer; **granted→viewer + direct GRANT `deals.update`** with reason "Special access for senior viewer"; **denied→tenant-admin + direct DENY `deals.delete`** with reason "Demo: deny override on admin role".
- Seed CRM data.

**`seedCrmData(tenant, dept, owner, manager)`**: 5 Companies (active), each with 2 Contacts + 1 Deal (`stage=proposal, status=draft`) + 2 Tasks attached morph to Deal (`status=open`) + 3 Activities attached morph to Deal.

---

## 17. Views (Blade + Tailwind 4)

### 17.1 Layouts

- **`layouts/app.blade.php`**: `html.h-full bg-gray-50`, includes `@vite(['resources/css/app.css','resources/js/app.js'])`, csrf meta, body with `.min-h-full.flex`. Includes `partials.sidebar` and `partials.topbar` only when `@auth`. Renders `status` and `error` flash banners. `@yield('content')` inside a `max-w-7xl` main.
- **`layouts/guest.blade.php`**: centered card layout, app name title, content slot.

### 17.2 Partials

- **`partials/sidebar.blade.php`**: dark `bg-gray-900 text-gray-100 w-64` aside. Shows tenant name + user name/email/Super Admin badge. Platform section (super-admin only) with links: Tenants / Permissions catalog / Global audit. Tenant section (when `app('current_tenant')` bound): CRM links (Dashboard, Companies, Contacts, Deals, Tasks, Activities) and Administration links (Users, Roles, Permissions, Departments, Audit log). Active state via `request()->routeIs('...')`. Logout `form` at bottom.
- **`partials/topbar.blade.php`**: white header with `@yield('header')` and `@yield('header-meta')`.

### 17.3 Auth

- **`auth/login.blade.php`** extends `guest`. Form posting to `route('login')`, email/password, remember checkbox, indigo submit. Show validation errors.
- **`auth/invitation.blade.php`** extends `guest`. Shows tenant name + invited email. Inputs: name, password, password_confirmation. POST to `route('invitation.accept', $invitation->token)`.

### 17.4 Dashboard

- **`dashboard.blade.php`**: 5-column grid of stat cards (Users, Companies, Contacts, Deals, Activities), then "Recent deals" card with last 5 deals.

### 17.5 Admin

- **`admin/users/index.blade.php`**: 2-col layout; left = paginated user table with role pills; right = invite form (visible if user can invite) and pending invitations list (with token link).
- **`admin/users/show.blade.php`**: user info card + roles checkbox form (PUT to `admin.users.roles.sync`) + direct permissions summary (with link to edit).
- **`admin/roles/index.blade.php`**: table with name/slug/level/users_count/permissions_count + edit/delete actions; "New role" button respects policy.
- **`admin/roles/create.blade.php`**: form name/slug/description/level (0-100).
- **`admin/roles/edit.blade.php`**: 2-form layout: left = role attrs; right = permissions checkboxes grouped by module (using `groupedByModule()`), PUT to `admin.roles.permissions.sync`.
- **`admin/permissions/index.blade.php`**: list permissions grouped by module name.
- **`admin/permissions/user-edit.blade.php`**: left = list of user's direct grants/denies (revoke buttons); right = add direct permission form (select permission_id, type, expires_at, reason).
- **`admin/departments/index.blade.php`**: table + inline rename forms + delete; sidebar form to create.
- **`admin/audit/index.blade.php`**: action filter dropdown, export CSV button if user has `audit.export`, table of logs with when/action/user/target/ip.

### 17.6 CRM

Every list/show/form view uses indigo primary buttons, `bg-white rounded-lg border border-gray-200 shadow-sm` cards. Use `@can('action', $model)` blocks to gate UI buttons. Index pages paginate. Forms split create/edit pages and a shared `_form.blade.php` partial when sensible.

- **Companies**: index, show, create, edit, `_form` partial with name/industry/email/phone/website/address/notes/status/owner.
- **Contacts**: similar pattern with first_name/last_name/email/phone/position/company/owner.
- **Deals**: show page has Approve button gated by policy + Edit button; `_form` covers all fields incl. company/contact/department/owner selects and stage/status enums.
- **Tasks**: show page has Mark complete button (gated, hidden if already done) and Edit; `_form` includes datetime-local due_date.
- **Activities**: list + create only.

### 17.7 SuperAdmin

- **`super-admin/tenants/index.blade.php`**: paginated table with name/slug/users/roles/status + "New tenant" button.
- **`super-admin/tenants/create.blade.php`**: name/slug/is_active.
- **`super-admin/tenants/show.blade.php`**: header with slug + status + Suspend/Activate button; "Features" panel with toggle per feature posting to `super-admin.tenants.features.toggle`.
- **`super-admin/permissions/index.blade.php`**: read-only catalog grouped by module with hint to add cases to `Permission` enum and re-seed.
- **`super-admin/audit/index.blade.php`**: tenant + action filters, paginated table.

---

## 18. Tests (Pest 4, must pass)

### 18.1 `tests/Pest.php`

```php
pest()->extend(TestCase::class)->use(LazilyRefreshDatabase::class)->in('Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function seedRbacCatalog(): void {
    (new ModuleSeeder)->run(); (new PermissionSeeder)->run(); (new FeatureSeeder)->run();
}

function makeTenant(string $slug='acme', string $name='Acme'): Tenant {
    seedRbacCatalog();
    $tenant = Tenant::factory()->create(['slug'=>$slug,'name'=>$name]);
    app(BootstrapTenant::class)->handle($tenant);
    return $tenant;
}

function tenantRole(Tenant $tenant, string $slug): Role { /* firstOrFail by tenant+slug */ }

function makeUserWithRole(Tenant $tenant, string $roleSlug, array $overrides = []): User {
    $u = User::factory()->create(array_merge(['tenant_id'=>$tenant->id], $overrides));
    $u->roles()->attach(tenantRole($tenant, $roleSlug)->id, ['assigned_at'=>now()]);
    return $u;
}
```

### 18.2 Unit tests

- **`ResolveUserPermissionsTest`**: super gets all 42; merging multi-role yields combined perms (sales+manager → has `deals.approve`, `audit.view`); direct grant on viewer adds `deals.update` (and `hasPermission()` true); direct deny on tenant-admin removes `deals.delete`; expired direct permission ignored; expired role assignment yields empty set.
- **`TenantAuthorizerTest`**: super always allowed; inactive user denied with reason containing "inactive"; inactive tenant denied likewise; cross-tenant resource denied with "Cross-tenant"; viewer denied for `deals.delete`; sales allowed for `deals.view`.
- **`TenantScopeTest`**: companies created across two tenants; setting `Context::add('tenant_id', tenant->id)` returns 3; super-admin acting → returns 5; user with `tenant_id` of "other" returns 2; `withoutGlobalScopes()` returns 5.
- **`RoleAssignmentConstraintTest`** via `AssignRolesToUser`: throws on auditor+manager; throws on manager assigning tenant-admin to member (higher than self); allows tenant-admin assigning sales; throws when role from different tenant.

### 18.3 Feature tests

- **`AuthFlowTest`**: guest `/` → redirect login; `/login` 200 with "Sign in"; tenant user login redirects to tenant.dashboard; super-admin login redirects to super-admin.tenants.index; wrong password yields session error.
- **`TenantIsolationTest`**: acme admin → `/t/globex` 403; CRM index only shows current tenant; cross-tenant show returns 404 (global scope); super-admin can view any tenant.
- **`RbacAuthorizationTest`**: sales can create companies; viewer cannot create (403); sales cannot delete (403); tenant-admin can delete; inactive tenant-admin → 403 on index.
- **`DirectPermissionTest`**: viewer + direct grant on `deals.create` can store deal; tenant-admin + direct deny on `deals.delete` gets 403 on delete.
- **`InvitationFlowTest`**: admin can invite + invitation stored with role_id; **manager + direct grant of `users.invite` cannot invite a tenant-admin role (role escalation)**; accept flow creates user with role and marks invitation `accepted_at`; expired invitation page returns 410.
- **`DealPolicyTest`**: blocks update on non-draft deal; owner can update draft; intruder from different department blocked; manager can approve at 11:00 on weekday (sets stage to Won); manager cannot approve at 22:00; manager cannot approve on Saturday. Use `Carbon::setTestNow(Carbon::create(2026,5,12,11,0,0))` and similar.
- **`SuperAdminPanelTest`**: super-admin can view tenants page; tenant-admin gets 403; super-admin can create+bootstrap a tenant (`tenant->roles()->count() === 5`, `tenant->departments()->count() === 1`).
- **`FeatureFlagTest`**: with `audit.export` permission but no feature → flash error; with feature attached → 200 success.
- **`AuditLogTest`**: creating a Company via HTTP writes an `AuditLog{action:'created', user_id, tenant_id, auditable_type=Company}`; permission denial path (viewer trying delete) hits expected 403; successful login is recorded with `action:'login'`.
- **`ExampleTest`**: `/` redirects guests to login.

All tests use the helpers + `$this->actingAs(...)`. Total target: **56 tests, ≥101 assertions**, run in ~1.6s with SQLite `:memory:`.

---

## 19. Acceptance criteria checklist

- [ ] `composer install && npm install && npm run build && touch database/database.sqlite && php artisan key:generate && php artisan migrate:fresh --seed` succeeds end-to-end.
- [ ] `php artisan route:list` shows **74 routes** including the 5 ResourceController expansions.
- [ ] `php artisan test --compact` passes with no failures.
- [ ] `vendor/bin/pint --test` produces zero diffs after running `vendor/bin/pint` once.
- [ ] Logging in as `super@enterprise-rbac.test / password` lands on `/super-admin/tenants` and can switch into `/t/acme` and `/t/globex` to see data.
- [ ] `admin@acme.test / password` is redirected to `/t/acme`, can see sidebar with both CRM and Administration, can manage users/roles/permissions/audit; cannot access `/t/globex` (403).
- [ ] `granted@globex.test` (viewer) can submit `POST /t/globex/crm/deals` thanks to direct grant of `deals.update`/`deals.create`.
- [ ] `denied@globex.test` (tenant-admin) cannot delete deals (direct deny overrides).
- [ ] Approving a deal as `manager@acme.test` works between 09:00-17:59 on weekdays; otherwise returns 403.
- [ ] Disabled tenant (`is_active=false`) returns 403 for all of its members.
- [ ] Disabled feature `audit_export` makes the CSV button submit fail with a flash error.
- [ ] Expired role assignment (Carbon time travel) removes permissions for that user.

---

## 20. Style and code conventions

- `declare(strict_types=1);` at top of every PHP file.
- Strict types in method signatures + return types everywhere; nullable types where appropriate.
- Use **`final readonly class`** for: Actions, Authorization services, Policies, Listeners, `RoleDefinition`.
- Use PHP enums (string-backed) instead of constants.
- Use Action classes for any non-trivial mutation; keep Controllers thin.
- Form validation via `FormRequest`. Use `Rule::enum(...)` for enum fields.
- All authorization goes through `Gate` → `Policy` → `TenantAuthorizer`. Middleware `permission:` is **only** for route-level checks; controllers prefer `$this->authorize(...)`.
- Multi-tenancy enforced via `BelongsToTenant` + `TenantScope` + `ResolveTenant` middleware + `Context::get('tenant_id')`.
- Audit logging happens automatically via `Auditable` trait; explicit events via `LogAuditEvent::handle`.
- Cache permissions via `database` driver; invalidate via `ForgetUserPermissionsCache` on every role/permission mutation.
- Tailwind classes only; never inline styles. Stick to: white cards `bg-white rounded-lg border border-gray-200 shadow-sm`; indigo primary buttons; gray-900 sidebar; red destructive; green confirm; status pills with `inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-*`.
- No `Model::preventLazyLoading()` (causes test fragility).
- Listeners registered manually in `AppServiceProvider::boot()`, not via `EventServiceProvider` discovery.

---

## 21. Deliverables

1. The full Laravel project as described above.
2. `README.md` containing project description, install instructions, roles/permissions tables, demo credentials (all `password`), and manual testing scenarios.
3. This `PROMPT.md` retained.

When finished, run:

```
php artisan migrate:fresh --seed
php artisan test --compact
vendor/bin/pint --dirty
```

All three must succeed without errors.
