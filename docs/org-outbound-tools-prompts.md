# Organization hierarchy and outbound tools — design prompts

Stored 2026-09-30. These are the design briefs. They are not the design report.

The first brief is the organization hierarchy and outbound tool registration model. The second is the follow-up that asks for ancestor and descendant walks as a composable SQL relation. The Dynamic Registration persistence brief is in `lti-dynamic-registration-prompt.md`.

---

We want to add a small, clean first-pass organization hierarchy and outbound LTI tool registration/deployment model to Tsugi.

Please inspect the existing Tsugi codebase first and follow existing database, migration, PHP service, naming, and PHPUnit conventions. Do not introduce a new framework or abstraction layer unless the existing codebase clearly calls for it.

Keep this change deliberately narrow.

The goal is to support:

1. An organization hierarchy within an `lti_tenant`.
2. Optional placement of an `lti_context` into an organization.
3. Outbound LTI tool registrations owned by a tenant and optionally scoped to an organization subtree.
4. Outbound LTI tool deployments to either:
   - one organization, or
   - one individual course/context,
   but never both in the same deployment row.
5. Recursive hierarchy operations and strong unit tests.

Do not implement the full outbound LTI 1.3 launch flow yet.

Do not implement OneRoster import yet.

Do not implement billing.

Do not implement organization administrators yet.

Do not redesign the existing inbound LTI tenant/deployment model.

This work is specifically about the platform/outbound side of Tsugi.

## Core model

The intended conceptual model is:

```text
lti_tenant
    |
    +-- lti_org
    |      |
    |      +-- child lti_org
    |      |
    |      +-- lti_context
    |
    +-- lti_tool_registration
             |
             +-- lti_tool_deployment
                      |
                      +-- exactly one of:
                          lti_org
                          lti_context
```

The important concepts are:

```text
tenant
    security/data boundary

org
    optional administrative hierarchy inside a tenant

context
    course; belongs to a tenant and may optionally belong to one org

tool registration
    outbound LTI registration created within a tenant,
    optionally limited to an org subtree

tool deployment
    one registration deployed to exactly one target:
    either an org or a context
```

## lti_org

Add an `lti_org` table.

Conceptually:

```text
lti_org
    org_id
    tenant_id          NOT NULL
    parent_org_id      NULL
    title
    org_type           NULL
    timestamps / other standard Tsugi metadata
```

Semantics:

- Every org belongs to exactly one tenant.
- `parent_org_id = NULL` means this is a top-level org in that tenant.
- Multiple top-level orgs are allowed.
- A child org must belong to the same tenant as its parent.
- Cycles are forbidden.
- The hierarchy must not depend on `org_type`.
- Do not model the tenant itself as a fake org row.

Example:

```text
Tenant A

University
    Engineering
        Computer Science
        Mechanical Engineering
    LSA
```

## lti_context relationship

Add:

```text
lti_context.org_id NULL
```

A context:

- already belongs to an `lti_tenant`
- may optionally belong to one `lti_org`
- may not belong to more than one org
- if it has an org, the org must belong to the same tenant

Existing Tsugi contexts must continue working with:

```text
org_id = NULL
```

Do not force existing contexts into organizations.

## lti_tool_registration

Add an outbound-tool registration table:

```text
lti_tool_registration
    registration_id
    tenant_id             NOT NULL
    org_id                NULL
    created_by_user_id    NULL if appropriate
    title
    minimal registration metadata needed for future LTI work
```

The key semantics are:

```text
tenant_id
    owns the registration

org_id
    optional maximum organizational scope within which it may be deployed
```

If:

```text
org_id = NULL
```

the registration may be deployed anywhere in the tenant.

If:

```text
org_id = Engineering
```

the registration may be deployed to:

```text
Engineering
Computer Science
Mechanical Engineering
any descendant of Engineering
```

but not to:

```text
LSA
another tenant
```

The registration itself does not make the tool visible to any course.

Visibility comes from deployments.

## lti_tool_deployment

Add:

```text
lti_tool_deployment
    tool_deployment_id
    registration_id       NOT NULL
    org_id                NULL
    context_id            NULL
    deployment_id         optional/future LTI deployment identifier
    timestamps / metadata as appropriate
```

Each deployment row targets exactly one thing.

It is either:

```text
registration -> org
```

or:

```text
registration -> context
```

Never both.

Use a MySQL 8 `CHECK` constraint to enforce this in the database:

```sql
CHECK (
    (org_id IS NOT NULL AND context_id IS NULL)
 OR (org_id IS NULL AND context_id IS NOT NULL)
)
```

This must be a real database invariant, not merely PHP validation.

Use normal nullable foreign keys:

```text
org_id     -> lti_org
context_id -> lti_context
```

Both may use:

```text
ON DELETE CASCADE
```

because the XOR constraint guarantees only one target is active for any valid row.

So:

- deleting an org deletes deployments targeted to that org
- deleting a context deletes deployments targeted to that context

Also make deployment deletion cascade when its registration is deleted if that fits existing Tsugi conventions.

## Deployment as many-to-many

For organization deployment, `lti_tool_deployment` acts as the association table between:

```text
lti_tool_registration  * <--> *  lti_org
```

A registration can be deployed to many orgs.

An org can have many tool registrations deployed to it.

Likewise a registration can also have multiple individual course deployments.

Examples:

```text
Turnitin registration
    -> Computer Science org
    -> Mechanical Engineering org
    -> EECS 485 context
```

Those are three separate deployment rows.

Do not make one deployment row target both an org and a context.

## Direct course deployment

A context with:

```text
org_id = NULL
```

may still receive a direct tool deployment.

This is important.

Organization membership is optional and must not be required in order to deploy an outbound LTI tool directly to a course.

So:

```text
Context A
    tenant_id = 10
    org_id = NULL
```

may still have:

```text
Tool Registration R -> Context A
```

provided the registration belongs to the same tenant and its scope permits that deployment.

For a registration with `org_id = NULL`, this is straightforward.

For a registration scoped to an org, a context must belong to that org or one of its descendants in order to receive the deployment.

A context with no org cannot receive a deployment from an org-scoped registration.

## Organization service

Create a small organization service using existing Tsugi service conventions.

Do not over-engineer this.

Something approximately like:

```php
OrgService::createOrg(...)
OrgService::moveOrg(...)
OrgService::getAncestors(...)
OrgService::getDescendants(...)
OrgService::isDescendantOrSelf(...)
```

Use better names if existing Tsugi conventions suggest them.

All hierarchy operations must be tenant-aware.

### getAncestors()

Given:

```text
University
    Engineering
        Computer Science
```

calling:

```text
getAncestors(Computer Science)
```

should preferably return:

```text
Computer Science
Engineering
University
```

including self.

If existing code conventions strongly suggest excluding self, document the choice and test it consistently.

Use a recursive CTE under MySQL 8.

### getDescendants()

Given:

```text
Engineering
    Computer Science
    Mechanical Engineering
```

return:

```text
Engineering
Computer Science
Mechanical Engineering
...
```

preferably including self.

### isDescendantOrSelf()

Examples:

```text
isDescendantOrSelf(CS, Engineering) = true
isDescendantOrSelf(Engineering, Engineering) = true
isDescendantOrSelf(ME, CS) = false
```

This operation must never cross tenant boundaries.

### moveOrg()

Allow moving an org:

```text
under another org in the same tenant
```

or:

```text
to the tenant root
```

by setting `parent_org_id = NULL`.

Reject:

```text
org beneath itself
org beneath one of its descendants
org beneath an org in another tenant
```

## Tool registration/deployment service

Create a small service for outbound tool registration/deployment behavior.

Possible API:

```php
ToolRegistrationService::createRegistration(...)
ToolRegistrationService::canDeployToOrg(...)
ToolRegistrationService::canDeployToContext(...)

ToolDeploymentService::createDeployment(...)
ToolDeploymentService::getDeploymentsForContext(...)
ToolDeploymentService::getRegistrationsForContext(...)
```

Adjust naming to existing Tsugi conventions.

### canDeployToOrg()

Rules:

1. registration and org must belong to the same tenant
2. if `registration.org_id IS NULL`, deployment is allowed anywhere in that tenant
3. otherwise target org must be:
   - the registration's org itself, or
   - a descendant of that org

### canDeployToContext()

Rules:

1. registration and context must belong to the same tenant
2. if `registration.org_id IS NULL`, direct context deployment is allowed
3. otherwise:
   - context must have an `org_id`
   - context's org must equal or descend from registration.org_id

A context with `org_id = NULL` cannot receive a deployment from an org-scoped registration.

### getRegistrationsForContext()

For a context, visible outbound tools are the union of:

1. direct deployments where:

```text
deployment.context_id = context.context_id
```

2. org deployments attached to:
   - the context's org
   - any ancestor of the context's org

Example:

```text
Engineering
    Computer Science       <- Tool A deployed here
        EECS 280
```

EECS 280 sees Tool A.

If Tool B is directly deployed to EECS 280, EECS 280 also sees Tool B.

If Tool C is deployed to Engineering, EECS 280 sees Tool C.

A sibling course under Mechanical Engineering does not see Tool A unless another ancestor deployment makes it visible.

A context with `org_id = NULL` sees only direct context deployments.

## Database constraints and indexes

Use database-level constraints wherever practical.

Important invariants:

```text
org parent belongs to same tenant
context org belongs to same tenant
registration org belongs to same tenant
deployment target belongs to same tenant as registration
deployment is exactly one of org or context
org hierarchy is acyclic
deployment target is inside registration scope
```

Some of these cannot be expressed conveniently as simple foreign keys or CHECK constraints and should be enforced in the service layer.

Add appropriate indexes for:

```text
lti_org.tenant_id
lti_org.parent_org_id

lti_context.org_id

lti_tool_registration.tenant_id
lti_tool_registration.org_id

lti_tool_deployment.registration_id
lti_tool_deployment.org_id
lti_tool_deployment.context_id
```

Consider uniqueness for:

```text
(registration_id, org_id)
(registration_id, context_id)
```

so the same registration cannot accidentally be deployed twice to the same exact target.

If there is a strong LTI reason not to impose that uniqueness, explain it before omitting it.

## Unit tests

Please build strong unit tests before adding any UI.

Create at least two tenants.

Suggested fixture:

```text
Tenant A

University A
    Engineering
        Computer Science
            AI Lab
        Mechanical Engineering
    LSA

Contexts:
    EECS 280 -> Computer Science
    EECS 281 -> Computer Science
    ME 250   -> Mechanical Engineering
    HIST 101 -> LSA
    FREE 101 -> no org

Tenant B

University B
    Engineering
        Computer Science
```

### Organization hierarchy tests

Test:

- create root org
- create child org
- multiple roots allowed
- ancestor traversal
- descendant traversal
- deep hierarchy
- include-self behavior
- moving a subtree
- moving an org to root
- traversal results after moves

### Tenant-boundary tests

Reject:

- parent org from another tenant
- context assigned to org in another tenant
- registration scoped to org in another tenant
- cross-tenant org move
- cross-tenant deployment
- hierarchy traversal crossing tenants

### Cycle tests

Given:

```text
A
    B
        C
```

reject:

```text
A.parent = C
B.parent = B
```

### Registration-scope tests

Tenant-wide registration:

```text
registration.org_id = NULL
```

may deploy to:

```text
Engineering
CS
ME
LSA
EECS 280
FREE 101
```

within the same tenant.

Engineering-scoped registration may deploy to:

```text
Engineering
CS
ME
EECS 280
ME 250
```

but not:

```text
LSA
HIST 101
FREE 101
another tenant
```

CS-scoped registration may deploy to:

```text
CS
AI Lab
EECS 280
EECS 281
```

but not:

```text
ME
ME 250
LSA
```

### XOR deployment tests

Verify the database rejects deployment rows where:

```text
org_id = NULL
context_id = NULL
```

and where:

```text
org_id != NULL
context_id != NULL
```

Verify these are valid:

```text
org_id != NULL
context_id = NULL
```

and:

```text
org_id = NULL
context_id != NULL
```

### Cascade tests

Verify:

- deleting an org deletes deployments targeted to that org
- deleting a context deletes deployments targeted directly to that context
- unrelated deployment rows survive
- deleting a registration removes its deployment rows if registration cascade is used

### Many-to-many tests

Verify:

- one registration can deploy to multiple orgs
- one org can receive multiple registrations
- one registration can deploy to multiple individual contexts
- duplicate deployment to the same exact target is rejected if uniqueness constraints are adopted

### Context visibility tests

Given:

```text
Engineering
    CS                    <- Tool A deployed
        EECS 280          <- Tool B directly deployed
        EECS 281

    ME
        ME 250

Tool C deployed at Engineering
```

verify:

```text
EECS 280 sees Tool A, Tool B, Tool C
EECS 281 sees Tool A, Tool C
ME 250 sees Tool C
ME 250 does not see Tool A
```

For:

```text
FREE 101
org_id = NULL
```

verify:

- it sees a directly deployed tool
- it does not inherit any org deployments

## Migration strategy

Keep this additive and safe.

Likely sequence:

1. Create `lti_org`.
2. Add nullable `org_id` to `lti_context`.
3. Create `lti_tool_registration`.
4. Create `lti_tool_deployment`.
5. Add foreign keys, CHECK constraints, uniqueness constraints, and indexes.

Do not require any data migration that creates org rows for existing tenants or contexts.

Existing Tsugi installations should continue running with:

```text
zero lti_org rows
zero lti_tool_registration rows
zero lti_tool_deployment rows
lti_context.org_id = NULL
```

## Scope boundary

Do not extend this task into:

```text
dynamic registration UI
OIDC launch implementation
AGS
NRPS
OneRoster
billing
organization administrators
tenant deployment/inbound deployment redesign
Canvas developer-key compatibility
```

Those are future work.

The first-pass success criterion is:

> Tsugi can create and manipulate a tenant-scoped organization hierarchy, optionally place courses in that hierarchy, create an outbound tool registration scoped to a tenant or organization subtree, deploy that registration to either organizations or individual courses, and correctly determine which registered tools are available to a course.

Please first inspect the repository and propose:

1. concrete tables/columns
2. migration files
3. indexes and constraints
4. service classes/methods
5. unit-test locations and cases

Then implement the smallest clean version consistent with existing Tsugi architecture.

Keep unrelated refactoring out of this change.

---

More work from ChatGPT 
One additional design requirement for the organization service:

I do not want the normal hierarchy-query pattern to be:

```php
$org_ids = OrgService::getAncestors(...);
// return IDs to PHP

SELECT ...
WHERE org_id IN ( ...materialized PHP list... )
```

That causes an unnecessary second database operation and turns a relational operation into a PHP array.

I want ancestor/descendant traversal to be usable as a composable SQL relation so callers can perform the whole operation in one SQL query using a recursive CTE.

The desired SQL shape is approximately:

```sql
WITH RECURSIVE ancestors AS (
    SELECT org_id, parent_org_id
    FROM lti_org
    WHERE tenant_id = :tenant_id
      AND org_id = :org_id

    UNION ALL

    SELECT o.org_id, o.parent_org_id
    FROM lti_org o
    JOIN ancestors a
      ON o.org_id = a.parent_org_id
    WHERE o.tenant_id = :tenant_id
)
SELECT d.*
FROM lti_tool_deployment d
WHERE d.org_id IN (
    SELECT org_id
    FROM ancestors
);
```

Likewise, descendant traversal should be composable into another query.

Please inspect existing Tsugi SQL/helper conventions and propose a small mechanism for this. Do not build a general ORM or complicated query builder.

I am imagining a lightweight concept such as:

```php
$scope = OrgService::ancestorScope($tenant_id, $org_id);
```

where `$scope` represents a relational set of org IDs rather than a PHP array.

The caller might be able to use something conceptually like:

```php
$scope->cte()
$scope->in('d.org_id')
$scope->params()
```

or another small API that fits existing Tsugi conventions better.

For example:

```php
$scope = OrgService::ancestorScope($tenant_id, $context_org_id);

$sql = "
    WITH RECURSIVE {$scope->cte()}
    SELECT DISTINCT r.*
    FROM lti_tool_registration r
    JOIN lti_tool_deployment d
      ON d.registration_id = r.registration_id
    WHERE d.context_id = :context_id
       OR {$scope->in('d.org_id')}
";

$params = array_merge(
    $scope->params(),
    [':context_id' => $context_id]
);
```

The goal is not specifically this exact API. The goal is:

> Treat ancestor/descendant traversal as a reusable relational set that can be composed into larger SQL statements without first materializing IDs in PHP.

Please also consider whether a JOIN against the recursive CTE is sometimes cleaner than an `IN (SELECT ...)`, and allow the helper design to support that if it remains simple.

We may still want convenience methods such as:

```php
OrgService::getAncestors(...)
OrgService::getDescendants(...)
```

for cases where PHP actually wants the rows.

But those should not force database-facing code to materialize IDs and issue a second query.

Please keep these requirements:

- one database query for operations such as "get tool deployments visible to this context"
- tenant filtering must be built into the recursive CTE
- parameter binding must remain safe
- CTE aliases/parameter names must not collide when composed into larger queries
- avoid framework-sized abstractions
- generated SQL should remain easy for a Tsugi developer to read and debug
- add unit tests for the scope/composition behavior as appropriate

A key target use case is:

```text
visible tools for context =
    direct deployments to this context
    OR
    org deployments whose org is this context's org or any ancestor
```

That should be implementable as one SQL statement with one recursive CTE, not two round trips through PHP.
