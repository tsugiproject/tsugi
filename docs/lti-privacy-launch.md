# LTI privacy launch

The course-tools test page can send an LTI 1.3 privacy launch when the tool registered an `LtiDataPrivacyLaunchRequest` message.

This follows the IMS LTI Data Privacy Launch 1.0 draft (document version 2, issued 13 January 2021, status “review by IMS Contributing Members”, [this version](https://www.imsglobal.org/spec/lti-dp/v1p0/)). That text is a members-only, nearly final draft. It can still change before final release, or at final release. Tsugi will need to follow those changes. The draft is not a certificate that a platform or a tool complies with GDPR or any other privacy law. It is a way for a person to open a tool and ask that tool to act on one user’s data, usually to erase it or to hand it back.

A privacy launch is the same login as a resource link, with a smaller token.

The browser still starts at the tool’s login URL. The tool comes back to Tsugi’s authorization URL, and Tsugi posts a signed `id_token`. The target is the `target_link_uri` on the privacy message. The draft expects one privacy target per registration. The registration’s ordinary launch URL is not used. A registration that only registered a deep-link message still cannot receive a resource link until a deep link return supplies a target.

The token keeps the usual launch identity: issuer, client id, subject, deployment id, version `1.3.0`, the privacy target, the platform, and a return URL. The message type is `LtiDataPrivacyLaunchRequest`.

Three things a resource link carries are left off, because this launch is not about a course resource:

- `https://purl.imsglobal.org/spec/lti/claim/resource_link`
- `https://purl.imsglobal.org/spec/lti/claim/context`
- service claims for gradebook and roster access

`https://purl.imsglobal.org/spec/lti/claim/for_user` names the person whose data the tool should act on. On this test page that person is the same person who clicked Test, so `sub` and `for_user.user_id` are the same id. Name and email are copied onto both the launching user and `for_user` when the registration was granted those claims.

Context membership roles are not sent. The Instructor test role is sent as system Administrator (`http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator`). The Learner test role is sent as system User. `for_user.roles` is system User, the person the action is about. The draft recommends an administrator for this launch and still allows a tool to receive someone who is not an administrator.

The tool does the work. Tsugi does not delete or export the tool’s copy of the data. A tool that erases data is expected to make the person confirm that before it happens.
