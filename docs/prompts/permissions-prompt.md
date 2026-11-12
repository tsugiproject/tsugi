# System Permissions, Applications, and Grants — design prompt

Stored 2026-11-12. Revised the same day, after the registry slice, so sections 1–3 match the code. This is the design brief. It is not the design report.

Routes come from controllers. Permissions come from services. `Tsugi.php` assembles both. An earlier draft of this brief said `Tsugi.php` gathers routes from services. That was wrong.

Sections 1 through 3 are in place. The next pass starts at section 4. Leave `CoursesService::permissions()` as it is. Leave the service list on `Permission::SERVICES`.

---

# Tsugi Feature: System Permissions, Applications, and Grants

## Objective

Implement a lightweight, system-level permission framework for Tsugi. Users can apply for permissions, administrators can approve or deny applications, and Tsugi services can check permissions before performing protected operations.

The first use case is **Apply to Teach**, which grants the `course.create` permission.

The base class already exists. `Tsugi\Services\Service` (`lib/src/Services/Service.php`) is parallel to the controller base `Tsugi\Controllers\Tool`. `CoursesService` already extends it. Do not create a second base class, and do not reparent the other classes under `lib/src/Services/` in this pass.

## 1. Service.php base class

Controllers live in `lib/src/Controllers/`. Their common parent is `Tsugi\Controllers\Tool` (`lib/src/Controllers/Tool.php`). `lib/src/Controllers/Tsugi.php` registers routes by calling `::routes()` on an explicit list of those controllers.

`Tsugi\Services\Service` is the common base for Tsugi services. It is not a controller, and it does not replace `Tsugi\Controllers\Tool`. Do not confuse it with `Tsugi\Core\Tool`, which is a different class.

Permission declaration is the first shared service capability on this base. Other service-wide features may be added later. The base method is already in place:

```php
public static function permissions(): array
{
    return [];
}
```

Do not move controller actions, route tables, or anything else out of `Tool.php` into `Service.php`. Controllers keep extending `Tsugi\Controllers\Tool`.

### Service inheritance

Inspect how classes under `lib/src/Services/` are defined before changing them.

`CoursesService` extends `Service` and already overrides `permissions()` with `course.create`. Leave that declaration as it is.

Services that declare permissions extend `Service` and override `permissions()`. Leave the existing repositories, exceptions, documents, value objects, and `database.php` files alone. Do not reparent them just so they inherit an empty method. Do not change their constructors or behavior.

Do not move routes onto services. Do not refactor controller routing.

## 2. Service-defined permissions

Each participating service overrides `permissions()` to declare its capabilities.

The Courses controller does not declare permissions. A courses service does. `CoursesService` already declares:

```php
public static function permissions(): array
{
    return [
        'course.create' => [
            'title' => 'Create Courses',
            'description' => 'Create new courses in Tsugi',
            'requestable' => true,
        ],
    ];
}
```

Permission codes are stable, developer-defined identifiers.

There is no central hard-coded permission list and no database table containing permission definitions.

## 3. Assemble permissions in Tsugi.php

`Tsugi.php` already assembles the route registry by calling `::routes()` on controllers. Leave that list and that behavior unchanged.

`Permission::assemble()` merges `permissions()` from an explicit list, `Permission::SERVICES`. `CoursesService` is on that list. `Tsugi.php` calls `assemble()` in the same initialization that registers routes. `Permission::registry()` holds the result for later screens and authorization checks. A duplicate permission code aborts the merge.

The registry is reconstructed during normal application initialization, just like the route registry.

Do not scan the filesystem, compile a registry at deploy time, or store permission definitions in the database.

Do not build a separate service-discovery system. There is no existing discovery mechanism for services. `Permission::SERVICES` is the list. A future service is added there. `Tsugi.php` keeps calling `assemble()` and does not grow its own copy of the list.

## 4. Persistence model

Use three database tables:

**`site_scope`**

Provides a stable scope identifier for authorization. Initially, only the system scope is supported. Future versions may support tenants and organizations.

**`site_application`**

Stores user requests for permissions, including the applicant, permission code, scope, justification, status, reviewer, response, and timestamps.

**`site_grant`**

Stores active and historical permission grants, including user, permission code, scope, source, grantor, grant date, expiration, and revocation information.

Store permission codes directly as strings.

The service registry, not a database table, defines which permissions exist.

Follow existing Tsugi schema, migration, indexing, and model conventions. Schema changes go through `admin/upgrade.php` in this checkout.

When the grant table is created, copy `lti_user.create_courses`. Each non-deleted user with that flag set to 1 gets one system-scope `course.create` grant, `source` `create_courses`, and no grantor. Later upgrades run the same copy. Skip a user who already has any `course.create` grant in the system scope, including a revoked grant, so a revocation stays revoked. Leave the `create_courses` column in place. `CoursesService::canCreate()` does not read it. Site admins still pass. Everyone else needs `Permission::has('course.create')`. The courses dropdown and `Courses::createGateResponse()` both use `canCreate()`.

## 5. Authorization API

Provide a straightforward API such as:

```php
Permission::has('course.create');
```

With optional scope support:

```php
Permission::has('course.create', $scope_id);
```

The authorization implementation must:

- Deny unknown or unregistered permissions.
- Check the current authenticated user's grants.
- Respect expiration and revocation.
- Default to the system scope.
- Enforce authorization server-side.

Keep permission registration separate from grant evaluation. Controllers call `Permission::has()` before a protected action. They do not declare the permission.

## 6. Application and approval workflow

Implement a generic permission application process.

Users can view available requestable permissions, submit an application with justification, and inspect the status of their requests.

The logged-in user does this from the instructor home, `lib/src/Controllers/Instructor.php` and `templates/Instructor`, at `/instructor`. Leave `lib/src/Controllers/Settings.php` alone.

Administrators can review applications, approve or deny them, and provide a response.

Approval creates a grant in the same database transaction as the application status update.

Administrators can also hand out and revoke grants from a simple CRUD screen in admin, without an application. Follow the existing admin CRUD style.

Prevent duplicate pending applications and duplicate active grants.

Retain application history.

Use existing authentication, CSRF protection, and administrative authorization mechanisms.

## 7. First use case: Apply to Teach

`Tsugi\Services\Courses\CoursesService` registers `course.create`.

Users without the permission can select **Apply to Teach** on the instructor home.

Once an administrator approves, the user can create courses.

Course creation is gated by `Courses::createGateResponse()`, which calls `CoursesService::canCreate()`. Site admins pass. Everyone else needs `Permission::has('course.create')`. The courses dropdown reads the same `can_create` flag.

The user who creates a course becomes an instructor in that course through the existing course-membership mechanism.

A system-level course creation permission does not grant instructor privileges in other courses.

Revoking `course.create` prevents additional course creation without affecting previously created courses.

Do not build "apply for the ability to create LTI 1.1 keys" or "apply for the ability to create LTI 1.3 keys" in this pass. Those are later requestable permissions, meaning the ability to create keys, not a request for one key. Leave the current key-request flow in place.

## 8. Explicitly out of scope

Do not implement configurable roles, Sakai-style realms, organization inheritance, tenant-specific policies, OneRoster provisioning, SSO-based grants, LTI-derived grants, course migration, or general-purpose policy engines.

Do not build a separate service-discovery system.

Do not move routes from controllers onto services.

## 9. Tests and documentation

Already covered:

- Default empty permissions inherited from `Service`.
- Service overrides of `permissions()`.
- Permission registry construction from the explicit service list.
- Duplicate permission detection.

Still to add:

- Unknown permission denial.
- Application submission and approval.
- Grant creation, expiration, and revocation, including an administrator hand grant.
- Course creation authorization, including the existing site-admin pass.

Document how a future service extends `Service`, declares permissions, is added to `Permission::SERVICES`, and how a controller checks the grant.

## Implementation guidance

Inspect `lib/src/Controllers/Tool.php`, `lib/src/Controllers/Tsugi.php`, `lib/src/Services/Courses/CoursesService.php`, `Courses::createGateResponse()`, and the upgrade conventions before changing code.

Keep `Service.php` small and focused. It should establish a useful common superclass without prematurely inventing shared service behavior.

Implement the first use case end-to-end while keeping the underlying permission framework generic.

**Architectural principle:** `Tsugi\Controllers\Tool` is the common base for controllers. Controllers declare routes. `Tsugi\Services\Service` is the common base for services. Services declare permissions. `Tsugi.php` assembles both. The database stores applications and grants.
