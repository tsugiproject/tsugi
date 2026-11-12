# System Permissions, Applications, and Grants — design prompt

Stored 2026-11-12. This is the design brief. It is not the design report.

---

# Tsugi Feature: System Permissions, Applications, and Grants

## Objective

Implement a lightweight, system-level permission framework for Tsugi. Users can apply for permissions, administrators can approve or deny applications, and Tsugi services can check permissions before performing protected operations.

The first use case is **Apply to Teach**, which grants the `course.create` permission.

An important part of this work is introducing a new base class, `Service.php`, parallel to the existing `Tool.php`.

## 1. Create a new Service.php base class

Tsugi currently has `Tool.php`, but does not have `Service.php`.

Create `Service.php` following the architectural conventions of `Tool.php`, including appropriate namespace, location, autoloading, and documentation.

This class will become the common base class for Tsugi services.

Permission declaration is the first shared service capability that will use this base class. In the future, other service-wide features may be added.

Initially, keep the base class minimal:

```php
public static function permissions(): array
{
    return [];
}
```

Do not move existing functionality from `Tool.php` into `Service.php`. These classes serve different purposes.

### Service inheritance

Inspect how services are currently defined and registered.

Update the existing services to extend the new `Service` base class where appropriate, preserving their existing behavior.

Avoid unnecessary refactoring of service routing, constructors, or controllers.

Services that do not declare permissions will simply inherit the empty implementation.

## 2. Service-defined permissions

Each service can override `permissions()` to declare its capabilities.

For example, the Courses service might contain:

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

## 3. Extend Tsugi.php initialization

`Tsugi.php` already assembles the route registry by gathering routes from services.

Extend that existing process to gather permissions as well.

During service initialization:

1. Discover the existing services using the current mechanism.
2. Collect each service's `permissions()` declarations.
3. Validate and merge them into one in-memory permission registry.
4. Reject duplicate permission identifiers.
5. Make the registry available to permission-management screens and authorization checks.

The registry is reconstructed during normal application initialization, just like the route registry.

Do not introduce separate filesystem scanning, deployment-time compilation, or persistent registry synchronization.

Keep route registration behavior unchanged.

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

Follow existing Tsugi schema, migration, indexing, and model conventions.

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

Keep permission registration separate from grant evaluation.

## 6. Application and approval workflow

Implement a generic permission application process.

Users can view available requestable permissions, submit an application with justification, and inspect the status of their requests.

Administrators can review applications, approve or deny them, and provide a response.

Approval creates a grant in the same database transaction as the application status update.

Prevent duplicate pending applications and duplicate active grants.

Retain application history.

Use existing authentication, CSRF protection, and administrative authorization mechanisms.

## 7. First use case: Apply to Teach

The Courses service registers `course.create`.

Users without the permission can select **Apply to Teach**.

Once an administrator approves, the user can create courses.

The user who creates a course becomes an instructor in that course through the existing course-membership mechanism.

A system-level course creation permission does not grant instructor privileges in other courses.

Revoking `course.create` prevents additional course creation without affecting previously created courses.

Preserve existing administrator behavior.

## 8. Explicitly out of scope

Do not implement configurable roles, Sakai-style realms, organization inheritance, tenant-specific policies, OneRoster provisioning, SSO-based grants, LTI-derived grants, course migration, or general-purpose policy engines.

Do not build a separate service-discovery system.

## 9. Tests and documentation

Add focused tests for:

- Default empty permissions inherited from `Service`.
- Service overrides of `permissions()`.
- Permission registry construction.
- Duplicate permission detection.
- Unknown permission denial.
- Application submission and approval.
- Grant creation, expiration, and revocation.
- Course creation authorization.

Document how future services can extend `Service`, declare permissions, and check them.

## Implementation guidance

Inspect `Tool.php`, existing service classes, `Tsugi.php`, and database conventions before changing code.

Keep `Service.php` small and focused. It should establish a useful common superclass without prematurely inventing shared service behavior.

Implement the first use case end-to-end while keeping the underlying permission framework generic.

**Architectural principle:** `Tool.php` is the common base for tools. `Service.php` becomes the common base for services. `Tsugi.php` assembles their registrations. Services declare permissions; the database stores applications and grants.
