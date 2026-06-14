<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Controller;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tsugi\Util\U;
use Tsugi\Util\Stripe as StripeUtil;

/**
 * Stripe Checkout routes for premium / supporter payments.
 */
class Stripe extends Controller {

    const ROUTE = '/stripe';

    public static function routes(Application $app, $prefix = self::ROUTE) {
        $app->router->post($prefix.'/webhook', function (Request $request) use ($app) {
            return Stripe::postWebhook($app);
        });
        $app->router->post($prefix.'/webhook/', function (Request $request) use ($app) {
            return Stripe::postWebhook($app);
        });
        $app->router->get($prefix.'/success', function (Request $request) use ($app) {
            return Stripe::getSuccess($app, $request);
        });
        $app->router->get($prefix.'/success/', function (Request $request) use ($app) {
            return Stripe::getSuccess($app, $request);
        });
        $app->router->get($prefix.'/cancel', function (Request $request) use ($app) {
            return Stripe::getCancel($app);
        });
        $app->router->get($prefix.'/cancel/', function (Request $request) use ($app) {
            return Stripe::getCancel($app);
        });
        $app->router->get($prefix, function (Request $request) use ($app) {
            return Stripe::getCheckout($app);
        });
        $app->router->get($prefix.'/', function (Request $request) use ($app) {
            return Stripe::getCheckout($app);
        });
        $app->router->post($prefix, function (Request $request) use ($app) {
            return Stripe::postCheckout($app);
        });
        $app->router->post($prefix.'/', function (Request $request) use ($app) {
            return Stripe::postCheckout($app);
        });
    }

    public static function checkoutUrl($CFG): string
    {
        return rtrim(Tool::configuredHomeUrl(), '/') . self::ROUTE;
    }

    public static function getCheckout(Application $app)
    {
        global $CFG, $OUTPUT;

        $home = Tool::configuredHomeUrl();
        $checkout_url = self::checkoutUrl($CFG);

        $user_id = U::loggedInUserId();
        if ($user_id <= 0) {
            Login::setReturnUrl($checkout_url);
            return new RedirectResponse(Login::loginUrl());
        }

        $site_name = $CFG->supporterSiteName();
        $site_label = $CFG->supporterSiteLabel();
        $supporter_label = $CFG->supporterLabel();
        $price_phrase = $CFG->supporterPricePhrase();
        $premium_period = $CFG->premiumMonthsLabel();
        $refund_policy = $CFG->refundPolicy();
        $profile_url = rtrim($home, '/') . '/profile';
        $home_url = rtrim($home, '/') . '/';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        ?>
<main id="container">
<h1><?= htmlspecialchars($supporter_label) ?></h1>
<p>
Support <?= htmlspecialchars($site_label) ?> with a one-time payment<?php if ($price_phrase !== '') { ?>
 of <?= htmlspecialchars($price_phrase) ?><?php } ?>.
You will receive <?= htmlspecialchars($premium_period) ?> of <?= htmlspecialchars($supporter_label) ?> status.
</p>
<p>The exact amount in your currency is shown on the next screen.</p>
<p>You will be redirected to our payment provider to complete checkout securely.</p>
<?php if ($refund_policy !== '') { ?>
<p><em><?= htmlspecialchars($refund_policy) ?></em></p>
<?php } ?>
<form method="post" action="">
<p>
<button type="submit" class="btn btn-success">Continue to payment</button>
<a href="<?= htmlspecialchars($profile_url) ?>" class="btn btn-default">Cancel</a>
</p>
</form>
<p style="margin-top: 1.5em;">
<a href="<?= htmlspecialchars($profile_url) ?>">Back to profile</a>
|
<a href="<?= htmlspecialchars($home_url) ?>">Back home</a>
</p>
</main>
        <?php
        $OUTPUT->footerStart();
        $OUTPUT->footerEnd();
        return '';
    }

    public static function postCheckout(Application $app)
    {
        global $CFG;

        $home = Tool::configuredHomeUrl();
        $checkout_url = self::checkoutUrl($CFG);

        $user_id = U::loggedInUserId();
        if ($user_id <= 0) {
            Login::setReturnUrl($checkout_url);
            return new RedirectResponse(Login::loginUrl());
        }

        $cfg = StripeUtil::config();
        $stripe = StripeUtil::client($cfg['secret_key']);

        $base = rtrim($home, '/');
        $success_url = $base . self::ROUTE . '/success?session_id={CHECKOUT_SESSION_ID}';
        $cancel_url = $base . self::ROUTE . '/cancel';

        $session_params = array(
            'mode' => 'payment',
            'adaptive_pricing' => array(
                'enabled' => true,
            ),
            'line_items' => array(
                array(
                    'price' => $cfg['supporter_price'],
                    'quantity' => 1,
                ),
            ),
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'client_reference_id' => (string) $user_id,
            'metadata' => array(
                'user_id' => (string) $user_id,
                'site' => $cfg['site'],
                'premium_months' => (string) $CFG->premiumMonths(),
            ),
        );

        $email = isset($_SESSION['email']) ? trim((string) $_SESSION['email']) : '';
        if ($email !== '') {
            $session_params['customer_email'] = $email;
        }

        try {
            $session = $stripe->checkout->sessions->create($session_params);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe checkout API error: ' . $e->getMessage());
            StripeUtil::fail('Stripe API error: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log('Stripe checkout error: ' . $e->getMessage());
            StripeUtil::fail('Unexpected error creating checkout session: ' . $e->getMessage());
        }

        if (empty($session->url)) {
            StripeUtil::fail('Stripe Checkout Session created but no redirect URL was returned.');
        }

        return new RedirectResponse($session->url, 303);
    }

    /**
     * Stripe webhook fulfillment.
     *
     * Local dev: stripe listen --forward-to https://local.py4e.com/stripe/webhook
     */
    public static function postWebhook(Application $app)
    {
        $cfg = StripeUtil::config(true);
        StripeUtil::requireSdk();

        $payload = @file_get_contents('php://input');
        if ($payload === false || $payload === '') {
            StripeUtil::fail('Empty webhook payload', 400);
        }

        $sig_header = StripeUtil::webhookSignatureHeader();
        if ($sig_header === '') {
            StripeUtil::fail('Missing Stripe-Signature header', 400);
        }

        try {
            $event = StripeUtil::constructEvent($payload, $sig_header, $cfg['webhook_secrets']);
        } catch (\UnexpectedValueException $e) {
            error_log('Stripe webhook invalid payload: ' . $e->getMessage());
            StripeUtil::fail('Invalid payload', 400);
        }

        $type = (string) StripeUtil::val($event, 'type', '');
        if ($type !== 'checkout.session.completed') {
            return new Response(
                "Ignored event type: {$type}\n",
                200,
                array('Content-Type' => 'text/plain; charset=utf-8')
            );
        }

        $session = StripeUtil::val($event, 'data', null);
        $session = is_object($session) ? StripeUtil::val($session, 'object', null) : null;
        if (!is_object($session)) {
            StripeUtil::fail('Missing checkout session object in event', 400);
        }

        $session_id = (string) StripeUtil::val($session, 'id', '');
        if ($session_id === '') {
            StripeUtil::fail('Missing checkout session id in event', 400);
        }

        try {
            $stripe = StripeUtil::client($cfg['secret_key']);
            $session = StripeUtil::retrieveCheckoutSession($stripe, $session_id);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe webhook retrieve session failed: ' . $e->getMessage());
            StripeUtil::fail('Could not retrieve checkout session', 500);
        }

        $result = StripeUtil::fulfillCheckoutSession($session, $cfg['supporter_price']);

        if (!$result['ok']) {
            error_log('Stripe webhook fulfillment failed session=' . $session_id . ' error=' . $result['error']);
            return new Response(
                'Fulfillment failed: ' . $result['error'] . "\n",
                200,
                array('Content-Type' => 'text/plain; charset=utf-8')
            );
        }

        if (!empty($result['skipped'])) {
            $body = "Already fulfilled for session {$session_id}\n";
        } else {
            $body = "Supporter granted until {$result['premium_until']} (profile_id={$result['profile_id']})\n";
        }

        return new Response($body, 200, array('Content-Type' => 'text/plain; charset=utf-8'));
    }

    public static function getSuccess(Application $app, Request $request)
    {
        global $CFG, $OUTPUT;

        $home = Tool::configuredHomeUrl();
        $home_url = rtrim($home, '/') . '/';
        $site_name = $CFG->supporterSiteName();
        $site_label = $CFG->supporterSiteLabel();
        $supporter_label = $CFG->supporterLabel();

        $session_id = trim((string) $request->query->get('session_id', ''));
        $paid = false;
        $status_message = '';
        $fulfillment_note = 'Your ' . $supporter_label . ' status is activated by our payment webhook and may take a few seconds to appear.';

        if ($session_id === '' || !preg_match('/^cs_/', $session_id)) {
            $status_message = 'Missing or invalid checkout session id.';
        } else {
            try {
                $cfg = StripeUtil::config();
                $stripe = StripeUtil::client($cfg['secret_key']);
                $session = StripeUtil::retrieveCheckoutSession($stripe, $session_id);
                $payment_status = (string) StripeUtil::val($session, 'payment_status', '');
                $paid = ($payment_status === 'paid');

                if ($paid) {
                    $status_message = 'Payment received. Thank you for supporting ' . $site_label . '!';
                } else {
                    $status_message = 'Checkout status: ' . $payment_status;
                }

                $session_user_id = (int) StripeUtil::val($session, 'client_reference_id', 0);
                $logged_in_user_id = U::loggedInUserId();
                if ($logged_in_user_id > 0 && $session_user_id > 0 && $logged_in_user_id !== $session_user_id) {
                    $status_message .= ' (This payment belongs to a different account.)';
                }
            } catch (\Exception $e) {
                error_log('Stripe success page error: ' . $e->getMessage());
                $status_message = 'Could not verify payment status right now. If you were charged, your supporter status will still be applied shortly.';
            }
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        ?>
<main id="container">
<h1>Thank you!</h1>
<?php if ($status_message !== '') { ?>
<p><?= htmlspecialchars($status_message) ?></p>
<?php } ?>
<?php if ($paid) { ?>
<p><?= htmlspecialchars($fulfillment_note) ?></p>
<?php } ?>
<p>
<a href="<?= htmlspecialchars($home_url) ?>">Back to <?= htmlspecialchars($site_name) ?></a>
|
<a href="<?= htmlspecialchars(rtrim($home, '/') . '/profile') ?>">View your profile</a>
</p>
</main>
        <?php
        $OUTPUT->footerStart();
        $OUTPUT->footerEnd();
        return '';
    }

    public static function getCancel(Application $app)
    {
        global $CFG, $OUTPUT;

        $home = Tool::configuredHomeUrl();
        $checkout_url = self::checkoutUrl($CFG);
        $home_url = rtrim($home, '/') . '/';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        ?>
<main id="container">
<h1>Checkout cancelled</h1>
<p>No charge was made. You can try again whenever you like.</p>
<p>
<a href="<?= htmlspecialchars($checkout_url) ?>">Try checkout again</a>
|
<a href="<?= htmlspecialchars($home_url) ?>">Back home</a>
</p>
</main>
        <?php
        $OUTPUT->footerStart();
        $OUTPUT->footerEnd();
        return '';
    }
}
