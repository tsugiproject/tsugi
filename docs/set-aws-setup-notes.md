# Amazon SES Setup Runbook — CC4E / Tsugi

_Last reviewed: August 10, 2026_

This README records the actual setup path we used for Amazon Simple Email Service (SES), including the questions that came up and the answers/decisions we made. It is intended both as a repeatable setup checklist and as a reminder of why each piece exists.

The concrete setup described here uses:

- Sending domain: `cc4e.com`
- AWS Region: `us-east-2` (Ohio)
- Custom MAIL FROM domain: `bounce.cc4e.com`
- Configuration set: `cc4e-mail` (or the existing configuration set if already created under another name)
- SES event destination: `cc4e-sns-events`
- SNS topic: `cc4e-ses-events`
- HTTPS webhook: `https://www.cc4e.com/ses/sns`
- DNS provider: Cloudflare
- Application: PHP / Tsugi
- Suppression source of truth: application database (`mail_suppress`)
- No Lambda required
- No dedicated IP pool
- No tenant management
- Shared SES IPs
- Production access granted in `us-east-2`

> **Important:** SES resources are regional. The domain identity, production access, configuration sets, sending quotas, and related SES resources described here are in `us-east-2`. The web/application server itself may be in another AWS Region; that is fine. The application must explicitly use the SES `us-east-2` endpoint/region.

---

## 1. Mental model

The outbound architecture is:

```text
PHP / Tsugi
    |
    | SendEmail / SendBulkEmail
    v
Amazon SES (us-east-2)
    |
    | configuration-set events
    v
Amazon SNS topic: cc4e-ses-events
    |
    | HTTPS POST
    v
https://www.cc4e.com/ses/sns
    |
    v
mail_suppress / delivery bookkeeping
```

The responsibilities are:

- **SES** — sends the email.
- **Configuration Set** — tells SES what extra behavior/event publishing applies to a send.
- **SNS** — distributes SES events to subscribers.
- **PHP webhook** — receives and processes SES events.
- **`mail_suppress`** — durable application-level suppression state.
- **Send-time gate** — checks suppression before every bulk/marketing send.

No Lambda is required for this architecture.

---

# Part I — SES account and domain setup

## 2. Choose the AWS Region first

Before creating anything, select the SES AWS Region.

For this setup:

```text
us-east-2 (Ohio)
```

This matters because SES identities and production access are regional. If `cc4e.com` is verified in `us-east-2`, the application should construct its SES client with:

```php
'region' => 'us-east-2'
```

### FAQ: Does the web server need to be in the same AWS Region?

**No.** An EC2/PHP server in another Region can call SES in `us-east-2` normally.

The important thing is that the application sends to the SES Region where the identity is verified and where production access was granted.

---

## 3. Choose a pricing plan

For an ordinary educational/bulk sending application, start with the basic/Essentials SES plan unless there is a clear reason to buy a higher tier.

We deliberately skipped:

- Pro solely for the sake of sending
- Enterprise
- Dedicated IPs
- Tenant management

AWS pricing changes, so check the SES console/pricing page rather than treating old dollar figures in documentation as permanent.

---

## 4. Create the sending domain identity

In SES:

```text
SES
→ Configuration
→ Identities
→ Create identity
→ Domain
```

Use:

```text
cc4e.com
```

Do **not** use:

```text
www.cc4e.com
```

The domain identity should be the DNS domain, not the web hostname.

Verifying `cc4e.com` allows SES DKIM identity inheritance for addresses below the domain, such as:

```text
newsletter@cc4e.com
cc4e@cc4e.com
noreply@cc4e.com
```

---

## 5. Configure Easy DKIM

SES supplies **three CNAME records** for Easy DKIM.

### FAQ: Why are there three DKIM CNAMEs?

Because SES manages DKIM keys/selectors and key rotation. Three CNAME records are normal for SES Easy DKIM. Add all three.

Do not invent the values. Copy the records currently displayed by SES.

Typical shape:

```text
<selector1>._domainkey.cc4e.com  CNAME  <selector1>.dkim.amazonses.com
<selector2>._domainkey.cc4e.com  CNAME  <selector2>.dkim.amazonses.com
<selector3>._domainkey.cc4e.com  CNAME  <selector3>.dkim.amazonses.com
```

### Cloudflare note

When editing DNS inside the `cc4e.com` zone, Cloudflare may display or accept only:

```text
<selector>._domainkey
```

instead of:

```text
<selector>._domainkey.cc4e.com
```

That is normal. Cloudflare appends the zone name.

The CNAME target remains the full Amazon hostname.

These records must be ordinary DNS records, not HTTP-proxied records.

---

## 6. Configure a custom MAIL FROM domain

Use:

```text
bounce.cc4e.com
```

### FAQ: Do I have to create `bounce.cc4e.com` as a website or hosting account?

**No.**

There is no separate website, mailbox, A record, or application required. The subdomain exists for SES MAIL FROM behavior through its DNS records.

### FAQ: Do I create `bounce` in Cloudflare?

Yes, but only by adding the SES-provided DNS records. There is no separate “create subdomain” operation.

SES gives two records:

```text
MX   bounce.cc4e.com   10 feedback-smtp.us-east-2.amazonses.com
TXT  bounce.cc4e.com   "v=spf1 include:amazonses.com ~all"
```

Use the **exact values SES shows**.

### FAQ: In this MX value, what is the `10`?

```text
10 feedback-smtp.us-east-2.amazonses.com
```

`10` is the **MX priority**.

In Cloudflare enter approximately:

```text
Type:       MX
Name:       bounce
Mail server: feedback-smtp.us-east-2.amazonses.com
Priority:   10
```

Do not put `10` into the server-name field.

### Important

SES requires exactly one MX record for the custom MAIL FROM domain. Do not add unrelated MX records to `bounce.cc4e.com`.

---

## 7. MAIL FROM failure behavior

SES asks:

> What should SES do if the custom MAIL FROM MX record is not configured correctly?

We chose:

```text
Use default MAIL FROM domain
```

That allows SES to fall back to an `amazonses.com` MAIL FROM domain instead of rejecting the outgoing message.

For this application, graceful fallback is preferable to refusing to send.

---

## 8. Add DMARC

SES may suggest a DMARC record such as:

```text
Type: TXT
Name: _dmarc
Value: "v=DMARC1; p=none;"
```

`p=none` is a monitoring/non-enforcement policy. It is a reasonable starting point while establishing the sender.

Do not casually change to a stricter DMARC policy until the domain's legitimate senders are understood.

---

## 9. Wait for verification

After the DNS records are published, SES checks them.

The domain eventually shows:

```text
Verified
```

The custom MAIL FROM domain should also show successfully configured.

Cloudflare/DNS propagation is often quick but AWS notes that DNS verification can sometimes take substantially longer.

---

# Part II — Deliverability options and optional SES features

## 10. Virtual Deliverability Manager

VDM can expose deliverability information and optimized shared delivery.

For this setup, the general preference was:

```text
Virtual Deliverability Manager:   useful / enable if included in chosen plan
Engagement tracking:              OFF initially
Optimized shared delivery:        ON
```

### Why leave engagement tracking off initially?

Open/click tracking is not necessary to determine whether the sending list is healthy, and it introduces tracking pixels and/or link rewriting.

Delivery, bounce, complaint, and subscription events can be handled without tracking opens and clicks.

---

## 11. Auto Validation

If Auto Validation is available under the selected plan and the pricing is acceptable, it can be enabled at the account level and left at the SES-managed threshold.

Do not override it in every configuration set unless a specific configuration set needs different behavior.

Remember that validation/pricing changes over time. Check the current SES console and pricing page before making a cost assumption.

---

## 12. Dedicated IP pool

### FAQ: Do we need a dedicated IP pool?

**No, not for this setup.**

Use the SES shared pool, optionally with optimized shared delivery.

Dedicated IPs are more appropriate when there is enough steady sending volume to establish and maintain a dedicated IP reputation. Low or irregular volume can be a poor fit.

Leave:

```text
Dedicated IP pool: none
```

---

## 13. Tenant management

### FAQ: Do we need a tenant?

**No.**

Tenant management is useful when one SES account is isolating multiple customers/business units/applications and their reputations.

For one application/domain, it is unnecessary complexity.

### FAQ: Is a tenant “below” the SES account rather than above a domain?

Yes. A useful mental model is:

```text
AWS account
  └── SES Region
       ├── identities
       ├── configuration sets
       ├── SNS/event plumbing
       └── optional tenants
```

A tenant is a logical isolation mechanism inside SES, not something that must be created in order to verify a domain.

---

# Part III — Configuration Set

## 14. Create a configuration set

A configuration set is a named bundle of SES rules/options applied to outgoing messages.

Use a meaningful name such as:

```text
cc4e-mail
```

### FAQ: What is a configuration set?

Think:

```text
Domain identity      = who is allowed to send
Configuration set   = what SES should do/track when mail is sent
SNS                  = where SES reports what happened
```

A configuration set can publish delivery/bounce/complaint events and can carry other per-send settings.

The application may specify it during `SendEmail` / `SendBulkEmail`, or SES can apply a default configuration set associated with an identity.

---

## 15. Configuration set options

Recommended choices for this use case:

```text
Sending IP pool:              default/shared
Custom redirect domain:       OFF
TLS required:                 usually OFF
Maximum delivery duration:    default / unset
Reputation metrics:           ON
Auto Validation override:     OFF
Suppression override:         OFF
VDM override:                 OFF
Archive outbound email:       OFF
Tenant:                       none
Tags:                         optional
```

### FAQ: What is Maximum delivery duration?

It is the amount of time SES should continue retrying when a receiving server temporarily refuses mail.

For newsletter/course mail, leave the normal/default retry behavior.

A shortened duration makes more sense for time-sensitive messages such as one-time login codes.

### Why not require TLS?

If “TLS Required” is enabled, SES will refuse delivery if it cannot establish the required secure transport with the receiving mail server.

For ordinary course/newsletter mail, maximizing successful delivery is generally preferable unless the application has a policy requiring TLS-only delivery.

### Why enable reputation metrics?

It provides bounce/complaint metrics that can be viewed through CloudWatch and is useful while establishing the sender.

### Why not override the account suppression list?

The account-level suppression behavior should remain in force unless there is a deliberate reason for one configuration set to behave differently.

### Why turn archive off?

We do not need SES to archive a copy of every outgoing message for this system.

---

# Part IV — SES events → SNS → PHP webhook

## 16. Add an Event Destination to the configuration set

Inside the configuration set:

```text
Event destinations
→ Add destination
→ Amazon SNS
```

Name:

```text
cc4e-sns-events
```

Enable event publishing.

Select at least:

```text
Rejects
Deliveries
Hard bounces
Complaints
Delivery delays
Subscriptions
```

Open and Click events are not needed unless engagement tracking is intentionally enabled.

---

## 17. Create the SNS topic

SNS topics live in **Amazon Simple Notification Service**, not inside SES.

Create:

```text
SNS topic: cc4e-ses-events
```

Keep the SNS topic in the same Region as this SES setup when possible:

```text
us-east-2
```

Then select that topic as the configuration set's SNS destination.

### FAQ: What is SNS?

SNS is **Amazon Simple Notification Service**.

It is a publish/subscribe notification system:

```text
SES publishes an event
       ↓
SNS topic
       ↓
one or more subscribers
```

Subscribers can include:

- HTTPS endpoints
- Lambda
- SQS
- email
- other supported AWS consumers

For this project, SNS pushes events to the PHP webhook.

### FAQ: Is SNS our Lambda?

Not literally, but **SNS eliminates the need for Lambda in this architecture**.

We do not need AWS to run application code in Lambda. SNS can POST the event directly to our PHP endpoint.

```text
SES → SNS → PHP
```

is enough.

### FAQ: SNS versus SQS?

A useful simplified model:

```text
SNS = publish/push/fan-out now
SQS = durable queue; hold work until a consumer receives and deletes it
```

SNS retries failed HTTP delivery, but it is fundamentally a notification/fan-out system.

SQS is appropriate when a durable queue and explicit consumer acknowledgement/deletion are desired.

If this system later needs stronger buffering:

```text
SES → SNS → SQS → worker
```

is an option.

It is not necessary for the initial CC4E setup.

---

## 18. Build the HTTPS webhook before subscribing it

Webhook:

```text
POST https://www.cc4e.com/ses/sns
```

The endpoint should:

1. Accept SNS HTTP POSTs without requiring a login/session/cookie.
2. Read the raw JSON SNS envelope.
3. Verify the SNS signature before trusting the message.
4. Reject unexpected `TopicArn` values.
5. Handle `SubscriptionConfirmation`.
6. Handle `Notification`.
7. Be idempotent because SNS may retry.
8. Respond quickly.

### SNS signature verification

Do not trust an arbitrary POST just because it resembles an SNS message.

Verification should include:

- HTTPS certificate retrieval
- validation that the signing certificate is an SNS/AWS signing certificate
- normal certificate trust validation
- validation of the SNS signature
- validation that `TopicArn` equals the expected topic ARN

Store/configure the expected SNS topic ARN rather than accepting messages from arbitrary SNS topics.

---

## 19. SNS subscription handshake

Once the webhook is deployed:

```text
SNS
→ Topics
→ cc4e-ses-events
→ Create subscription
```

Use:

```text
Protocol: HTTPS
Endpoint: https://www.cc4e.com/ses/sns
```

SNS immediately sends a `SubscriptionConfirmation` POST.

The endpoint must verify the SNS message and then confirm the supplied subscription (for example by following the verified `SubscribeURL`).

SNS will not send ordinary notifications to the HTTP endpoint until the subscription is confirmed.

### FAQ: Did we need the webhook in production before finishing the SES event destination?

**No.**

It was safe to finish:

```text
SES configuration set → SNS topic
```

first.

The webhook needed to be ready only before creating the **SNS HTTPS subscription**, because that is when SNS sends the confirmation request.

---

# Part V — Application event handling and suppression

## 20. Durable application suppression table

The application owns its suppression state.

Representative schema:

```text
mail_suppress

suppress_id
email                 normalized lowercase, UNIQUE
reason                bounce | complaint | unsubscribe
scope                 all | bulk        (recommended)
detail
message_id
created_at
updated_at
```

Recommended semantics:

```text
Permanent bounce  → suppress all mail
Complaint         → suppress all mail
Unsubscribe       → suppress bulk/marketing
Transient bounce  → log only
```

Do not let a normal newsletter unsubscribe accidentally disable transactional/account-critical email unless that is explicitly the desired policy.

A reason precedence such as:

```text
complaint > bounce > unsubscribe
```

can keep an upsert from weakening an existing suppression state.

---

## 21. Gate every send

All mail should pass through one central send-time gate.

Conceptually:

```php
if (Mail::isSuppressed($email, $purpose)) {
    // Skip before calling SES
}
```

The suppression table is not merely informational. It must be checked immediately before transport.

---

## 22. Delivery events as “known good” evidence

A SES **Delivery** event means SES successfully handed the message to the recipient's mail server.

It is not proof that:

- the recipient opened it,
- it landed in the inbox rather than spam,
- a human read it.

But it is excellent list-hygiene evidence.

Maintain a field such as:

```text
last_delivered_at
```

rather than calling it “verified”.

Useful interpretation:

```text
Delivery     → recent evidence the destination accepted mail
Hard bounce  → bad; suppress
Complaint    → suppress
Unsubscribe  → address works, but recipient does not want this mail class
No event     → unknown/pending
```

---

## 23. One logical recipient per email

Prefer one recipient per logical SES message rather than a single MIME message with many BCC recipients.

This makes delivery/bounce/complaint correlation much cleaner:

```text
recipient
  ↓
SES message ID
  ↓
delivery/bounce/complaint
  ↓
one recipient record
```

For efficiency, SES `SendBulkEmail` can submit multiple independent destinations in a single API request.

---

# Part VI — Unsubscribe

## 24. SES does not automatically add an unsubscribe link

A configuration set and “bulk” classification do **not** automatically add an unsubscribe link.

There are two broad strategies:

1. SES Contact Lists / SES subscription management
2. Application-owned unsubscribe handling

We chose **application-owned unsubscribe handling** because Tsugi already has its own user/suppression state.

---

## 25. Add visible and one-click unsubscribe

For bulk/marketing email, add:

```text
List-Unsubscribe: <https://www.cc4e.com/util/unsubscribe.php?...signed params...>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

Also include a visible `Unsubscribe` link in the HTML/text footer.

Transactional email should not automatically receive these bulk unsubscribe headers.

### One-click endpoint behavior

The unsubscribe endpoint should support:

```text
GET
  → validate signed token
  → show confirmation UI
  → user confirms
  → mark unsubscribe

POST List-Unsubscribe=One-Click
  → validate signed token
  → no session/login requirement
  → immediately mark unsubscribe
  → return HTTP 200 quickly
```

The POST must be idempotent.

The URL should be a signed capability URL because Gmail/other mailbox providers may POST to it without the user's application session.

---

# Part VII — Production access

## 26. Sandbox meaning

While an SES account is in the sandbox, normal sending is restricted. Test recipient identities may need to be verified.

A “verified recipient” in this context means an email address whose owner completed AWS's SES verification process.

This is **not** the same thing as paid/automated email-address validation.

### Practical sandbox testing

Verify one or two email addresses you control and use those to test initial sending.

---

## 27. Request production access

Once:

- domain verification works,
- DKIM is working,
- MAIL FROM is configured,
- event handling is planned/working,

request production access.

When the form asks for one mail type and both could apply, choose whichever is the majority use case. For this setup we chose:

```text
Marketing
```

Use a website that clearly represents the sender, preferably:

```text
https://www.cc4e.com
```

Production access for this setup was granted in:

```text
us-east-2
```

Once out of the sandbox, ordinary unverified recipient addresses can be used subject to the account's sending quota/rate.

---

## 28. Check quota and rate before a large campaign

In the SES console, inspect:

- daily sending quota
- maximum send rate
- current reputation/bounce/complaint metrics

Do not assume a quota from another account or Region applies here.

Use the SES mailbox simulator for controlled testing of success/bounce/complaint behavior rather than intentionally creating harmful reputation events with random addresses.

---

# Part VIII — Cost notes

Costs change, so this section intentionally avoids treating specific 2026 numbers as permanent.

The deliberately inexpensive architecture is:

```text
SES shared sending
+ SNS HTTPS events
+ modest CloudWatch reputation metrics
+ existing PHP server
```

We intentionally avoided:

- dedicated IP charges
- tenant management complexity
- Lambda (not needed)
- unnecessary message archiving
- unnecessary open/click tracking

At modest tens-of-thousands-of-email scale, SES/SNS event plumbing is generally inexpensive, but always check the current AWS pricing pages and Cost Explorer.

CloudWatch can become expensive with large quantities of logs or high-cardinality custom metrics; this design does not require that.

---

# Part IX — FAQ recap

## Is incoming email possible with SES?

Yes, but it is a separate receiving architecture from the outbound setup in this README. SES can route received mail through receipt rules to services such as S3, Lambda, and SNS depending on the receiving feature/Region in use.

For a simple “run PHP when mail arrives” design, a small AWS bridge can POST to an application, but this is unrelated to the outbound SES→SNS event webhook described above.

## Does outgoing SES require Lambda for events?

No.

```text
SES → SNS → HTTPS/PHP webhook
```

works without Lambda.

## Do delivery events arrive one address at a time?

Do not design code around a fragile assumption about batching. Parse the recipients represented by the event and make the handler idempotent.

Using one logical recipient per SES message makes correlation much easier.

## Is an unsubscribe evidence that the address is valid?

Yes in the practical sense that a human/mail system received enough of the message to act on it—but it means “do not send this class of mail,” so it must still be suppressed appropriately.

## Should soft/transient bounces suppress immediately?

No. Log them. Suppress permanent/hard bounces.

## Should permanent bounces and complaints be treated seriously?

Yes. They should immediately suppress future sending. Good bounce/complaint handling protects sender reputation.

---

# Part X — IAM credentials for the application

This is intentionally the **last step**. Do not create application credentials until the SES configuration itself is ready.

## 29. First choice: use an IAM role if possible

If the PHP application runs on EC2 and the AWS environment permits attaching an IAM role to the instance/task, prefer temporary role credentials over long-lived access keys.

AWS recommends temporary credentials where practical.

If this is a university-managed AWS environment, ask whether an instance role can be granted the SES send permissions before creating a long-lived IAM user key.

If a role is not practical, create a dedicated IAM user for SES sending as described below.

---

## 30. Create a dedicated IAM policy

In AWS:

```text
IAM
→ Policies
→ Create policy
→ JSON
```

A simple send-only policy for the SES v2 APIs used by the application is:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "AllowSesSending",
      "Effect": "Allow",
      "Action": [
        "ses:SendEmail",
        "ses:SendBulkEmail"
      ],
      "Resource": "*"
    }
  ]
}
```

This allows sending but does not grant broad SES administration.

If the application also uses a legacy SES operation that requires another send action, add only that specific action after verifying it in the AWS Service Authorization Reference.

For a more locked-down production policy, restrict resources/conditions to the `cc4e.com` identity and expected configuration set/from-address rather than leaving `Resource` as `"*"`. Start simple, verify sending, then tighten deliberately.

Suggested policy name:

```text
cc4e-ses-send
```

---

## 31. Create a dedicated IAM user

If using long-lived credentials:

```text
IAM
→ Users
→ Create user
```

Suggested name:

```text
cc4e-ses-sender
```

This user does **not** need AWS Console access.

Attach only the custom send policy:

```text
cc4e-ses-send
```

Do not give it `AdministratorAccess`.

Do not use AWS root-account credentials.

---

## 32. Create the access key and secret

Open:

```text
IAM
→ Users
→ cc4e-ses-sender
→ Security credentials
→ Access keys
→ Create access key
```

Choose the use case corresponding to an application running outside a managed temporary-credential mechanism, acknowledging AWS's recommendation to prefer temporary credentials where possible.

AWS will produce:

```text
AWS Access Key ID
AWS Secret Access Key
```

### Critical behavior

The **secret access key is displayed only when the access key is created**.

Save it immediately in the application's secret-management/configuration mechanism.

If the secret is lost, it cannot simply be displayed again. Create a replacement key and retire/delete the old one.

Never:

- commit the key or secret to Git
- place it in a public config example
- paste it into issue trackers
- use the AWS root account access key
- expose it in HTML/PHP error output
- send it by email/chat as ordinary text

An IAM user can have at most two access keys at a time, which supports key rotation.

---

## 33. Application configuration

Conceptually the application needs:

```text
AWS region:             us-east-2
AWS access key ID:      <secret configuration>
AWS secret access key:  <secret configuration>
SES configuration set:  cc4e-mail
```

Example AWS SDK client shape:

```php
$client = new Aws\SesV2\SesV2Client([
    'version' => 'latest',
    'region' => 'us-east-2',
    // Prefer the SDK credential-provider chain.
    // Do not hard-code credentials in source.
]);
```

Prefer the AWS SDK credential-provider chain/environment/instance role over embedding credentials directly in PHP source.

If using environment variables, common AWS SDK variables are:

```text
AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
AWS_REGION=us-east-2
```

Protect the process environment and deployment secrets appropriately.

---

## 34. Final end-to-end test

Before sending a large campaign:

1. Confirm SES identity `cc4e.com` is verified in `us-east-2`.
2. Confirm Easy DKIM is healthy.
3. Confirm custom MAIL FROM `bounce.cc4e.com` is healthy.
4. Confirm production access is active in `us-east-2`.
5. Confirm `cc4e-mail` configuration set is being applied to sends.
6. Confirm SNS event destination publishes to `cc4e-ses-events`.
7. Confirm the HTTPS SNS subscription is `Confirmed`.
8. Send to a normal address you control.
9. Confirm a `Delivery` event reaches `/ses/sns`.
10. Use the SES mailbox simulator to exercise bounce/complaint paths safely.
11. Confirm permanent bounce/complaint writes to suppression.
12. Confirm a bulk unsubscribe writes `unsubscribe` suppression.
13. Confirm a suppressed bulk recipient is blocked before SES is called.
14. Confirm `last_delivered_at` or equivalent is updated on a delivery event.
15. Check the current SES sending quota and rate before launching the full mailing.
16. Start with a modest real batch and inspect reputation/events before scaling up.

---

# AWS documentation references

These are the AWS documentation areas most directly relevant to this runbook:

- SES identities / Easy DKIM  
  https://docs.aws.amazon.com/ses/latest/dg/creating-identities.html

- Custom MAIL FROM domain  
  https://docs.aws.amazon.com/ses/latest/dg/mail-from.html

- SES configuration sets  
  https://docs.aws.amazon.com/ses/latest/dg/using-configuration-sets.html

- SNS HTTP/HTTPS endpoint preparation and confirmation  
  https://docs.aws.amazon.com/sns/latest/dg/SendMessageToHttp.prepare.html  
  https://docs.aws.amazon.com/sns/latest/dg/SendMessageToHttp.confirm.html

- SNS message signature verification  
  https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message.html

- IAM access keys  
  https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html

- SES IAM access control  
  https://docs.aws.amazon.com/ses/latest/dg/control-user-access.html

- SES v2 IAM actions  
  https://docs.aws.amazon.com/service-authorization/latest/reference/list_sesv2.html

---

## Short version

If everything else in this README is forgotten, remember this:

```text
1. Pick SES Region.
2. Verify cc4e.com.
3. Add all three DKIM CNAMEs.
4. Configure bounce.cc4e.com MAIL FROM MX + SPF.
5. Add DMARC.
6. Use shared IPs; skip dedicated IP and tenant.
7. Create cc4e-mail configuration set.
8. Publish Delivery/Bounce/Complaint/etc. events to SNS.
9. Create cc4e-ses-events SNS topic.
10. Deploy HTTPS /ses/sns webhook with signature + TopicArn validation.
11. Subscribe webhook to SNS and confirm it.
12. Suppress permanent bounce, complaint, unsubscribe; log soft bounce.
13. Gate every bulk send.
14. Add standards-compliant one-click unsubscribe.
15. Request production access.
16. Prefer an IAM role; otherwise create a send-only IAM user.
17. Create access key/secret once, save securely, never commit them.
18. Send a small test batch before the big one.
```
