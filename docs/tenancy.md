# Tenancy

Status: design note. The first half is the schema as it works now. A later tenant is likely a grouping of keys and orgs. That grouping is not designed here, and nothing in this note renames a current table.

Related: [lti_key_self_service.md](lti_key_self_service.md), [issuer_migration.md](issuer_migration.md), [client-id-check.md](client-id-check.md).

## What a tenant is today

`lti_key` is the tenant unit the database has now. The schema comment calls it the billing endpoint. Users, courses, memberships, results, and the org tree hang off `key_id` and are deleted with the key.

| Table | Relationship to the key |
| --- | --- |
| `lti_user` | Contained. `key_id` is a foreign key, `ON DELETE CASCADE`. |
| `lti_context` | Contained. A course. Same cascade. `org_id` is optional and must be an org on this same key. |
| `lti_org` | Contained. A hierarchy **inside** one key. `key_id` is `NOT NULL` and cascades. A null `parent_org_id` is a top-level org in that key. The key itself is not an org row. |
| `lti_key.user_id` | Owner. Integer, **not** a foreign key, so a key and its users cannot form a circular constraint. |
| `lti_client` | Connected, not a parent. One OAuth client (`issuer` + `client_id`). Deployments stay on `lti_key.deploy_key`. `ON DELETE SET NULL` detaches keys; it does not delete them. |

`lti_org` is not a tenant and it is not a roll-up of keys. A later tenant, if we add one, is more likely a grouping of keys and of these org trees. The name and the columns are open. It will not be a reuse of `lti_org`.

`lti_tool_registration` is the other direction: Tsugi is the platform. `lti_client` is inbound: Tsugi is the tool.

## How a launch picks a key

An LTI 1.3 launch does **not** select the tenant by deployment id. `LTIX::loadAllData()` matches issuer and client id, orders by `key_id`, and takes the first row.

- A null issuer hash on the key matches any issuer, with that key's client id. That wildcard is not an `lti_client` row. `lti_client.issuer` is required.
- A key with no complete client matches `lms_issuer` (or the null wildcard) and `lms_client`.
- A key whose `lti_client_id` points at a complete client matches that client's issuer and client id instead. Complete means issuer, client id, OIDC URL, keyset URL, and token URL are all set. The client's audience, even when null, replaces the key's audience in memory. Stored key columns are not rewritten.
- `deploy_key` is not in the `WHERE`. The JWT deployment id is still copied onto the row for later service calls.

So two keys that share an issuer and client id are not two launch targets. The older `key_id` wins every launch. `UNIQUE(lms_issuer, lms_client, deploy_key)` can still store one row per deployment, and `UNIQUE(lti_client_id, deploy_key)` can still store one deployment per linked client. Those constraints do not choose the row at launch.

A blank deployment id is stored as `NULL`. In the unique indexes, `NULL` does not collide with another `NULL`. The key form rejects a second blank deployment for the same linked client in PHP, because the unique index will not.

Dynamic registration still writes the legacy columns on `lti_key`. It does not create an `lti_client` row. A complete linked client would hide those columns at launch. That handoff is later.

## Containment and connection

Containment is the cascade from `lti_key`: the key's users, courses, and orgs do not outlive it. Connection is a link between things that already exist. `lti_client` is a connection. Deleting it clears `lti_client_id` and leaves the courses alone.

`lti_key.user_id` stays a loose owner for the same reason the circular picture is a bad containment model: a user contained in a key cannot also be the foreign-key parent of that key. Authority, if we add it, has to be a connection, not another `NOT NULL` parent.

## Later: a grouping, not a schema

An instructor can have a working key before any larger tenant exists. A later tenant would probably associate existing keys and orgs, so an institution can administer and pay for them without new installs or moved courses. How that association is stored is open. One sketch, not a decision:

```text
lti_key          today's tenant unit. Holds users and courses.
lti_org          tree inside one key. Not a tenant.
lti_client       OAuth client. Not a tenant.
(unnamed)        possible later grouping of keys and orgs
```

A user stays on the key where the launch created them. A grant might let that user administer the grouping, and the grouping might point at several keys, including keys whose users are different people. Those would be connections. They would not be foreign keys that change `lti_user.key_id`, and they would not replace `lti_org`.

If several groupings can see one key, billing still has to pick one accountable party. An admin grant is not a bill and not a paid entitlement.

Which permissions cross from the grouping to a key would be an explicit list. Administration does not imply grades, billing, or delete. If that walk is ever recursive, it has to stop on cycles. Derived rights should be computed from current rows, not copied into a second grant that survives after the link is removed.

Deleting a key deletes the users on it. An identity that lives on a key cannot be the identity that deletes that key. Site admin is a separate gate (`AdminService::isAdmin()`), not a role on `lti_user`. The delete screen asks for the server name and the course and user counts because the cascade is real.

## Root access

Google login uses the key whose consumer key is `google.com`. Those users are ordinary `lti_user` rows on that key. Signing in with Google does not make someone a site admin. The admin console is its own check. The site home course on that key cannot be deleted from course settings.

That key is a reasonable place for accounts that are not an LMS launch. It is a bad place to store the only copy of an authority that must survive deleting ordinary keys. Repair of a later grouping should not depend on a user row that a key delete would cascade away.

## Registration scope is a policy, not the current router

People use "registration" for `(issuer, client_id)` and "deployment" for `(issuer, client_id, deployment_id)`. Today the launch router is registration-scoped for every platform, including Blackboard: one winning key per issuer and client id, whichever row was inserted first.

A later policy could be: some platforms share one key across deployments, and others get one key per deployment id, with the launch `WHERE` actually testing `deploy_key`. Unknown platforms would stay isolated until someone sets the policy. That is not built. A wildcard issuer or a blank deployment id is a property of a particular key, not a vendor rule.

Changing that policy after data exists splits or merges keys. It should be a reviewed edit, not a silent update of trust URLs. `lti_client` already refuses to be a second copy of the same issuer and client id. The admin Client Id Check reports keys that share an issuer and client id but disagree on OIDC, keyset, token URL, or audience.

## Free use, then a grouping

Nothing in the current schema requires a larger tenant before a key launches. The commercial path, if we build it, sits beside the key:

```text
Dynamic registration writes lti_key
        |
Launch resolves issuer + client id to one key
        |
Free use
        |
Someone associates the key with a later grouping
        |
Paid use, same key, same courses
```

Launch claims, email domains, and registration metadata can suggest a grouping. They do not create the association. An activation code, someone who already administers the grouping, or a site admin are ways to create it. Those are different bootstraps into the same link.

`lti_platform` is that evidence. One row is a snapshot from a dynamic registration or a validated LTI 1.3 launch. `platform_id` on a key is the latest snapshot for that key. `registration_platform_id` on a key or client is the original registration and does not move. A referenced row stays while the key or client does. These rows do not choose a launch, a deployment, or a tenant.

Entitlement, if we add it, is checked after the launch is valid and the key is chosen. It also has to cover APIs and jobs, or a limit only on the launch URL does not hold. A suspended key keeps its id and its courses. The next allowed launch should work without a new registration.

What to count (learners, courses, time) is not decided. A learner count is kinder than counting every launch. Messages students see should not be an invoice.

## Expiry that already exists

Admin → Expire can delete idle `lti_key` rows. `$CFG->expire_tenant_days` defaults to 800, commented as two years, and cannot be set below 80. The screen itself falls back to 1000 days when the setting is absent. This is an operator action, not a background job. Deleting the key cascades its users, courses, and `lti_org` rows.

A renewal button for free keys would be new. It should not count as course activity, or clicking it would preserve abandoned courses. A failed launch should not refresh `login_at` either, or a dead integration lives forever. Paid status does not by itself change the expire clock.

## Open decisions

- Whether launch selection grows a deployment-id predicate, and for which platforms.
- Whether a later tenant groups keys, orgs, or both, what it is called, and the rule when two groupings both point at one key.
- Which grants are inherited, and which identity is allowed to delete a key.
- Free-tier numbers, grace periods, and where a suspended launch lands.
- How many unreferenced platform observations to keep. A referenced row stays while its key or client does.

## Summary

- Today the tenant unit is `lti_key`. `lti_org` is a tree inside one key, not a tenant.
- A later tenant is likely a grouping of keys and orgs. That shape is not chosen.
- `lti_client` shares OAuth configuration across keys. It does not share their courses.
- Launch picks the oldest key for an issuer and client id. Deployment id does not choose the key.
