<?php

namespace Tsugi\Controllers\Api;

use Tsugi\Core\LTIX;
use Tsugi\Util\LTI;
use Tsugi\Util\Net;
use Tsugi\Util\U;

/**
 * LTI 1.1 basic-lis-readmembershipsforcontext.
 *
 * Moodle and Sakai added this. IMS documented it and then removed the page.
 * The Sakai notes are the spec we still follow:
 * https://github.com/sakaiproject/sakai/blob/master/basiclti/docs/sakai_basiclti_api.md
 *
 * POST /api/ltiextroster.php
 */
class LtiExtRoster {

    public function handle(): void
    {
        global $CFG, $PDOX;

        $membership_id = U::get($_POST,'id');
        if ( ! is_string($membership_id) ) $membership_id = '';
        // Parse the sourcedid
        $pieces = explode('::', $membership_id);
        if ( count($pieces) != 4 ) {
            Net::send400('Invalid sourcedid format');
            return;
        }
        if ( is_numeric($pieces[0]) && is_numeric($pieces[1]) && is_numeric($pieces[2]) ) {
            $key_id = $pieces[0];
            $context_id = $pieces[1];
            $link_id = $pieces[2];
            $incoming_sig = $pieces[3];
        } else {
            Net::send400('sourcedid requires 4 numeric parameters');
            return;
        }

        $sql = "SELECT K.secret, K.key_key, C.context_key, L.settings, L.placementsecret
            FROM {$CFG->dbprefix}lti_key AS K
            JOIN {$CFG->dbprefix}lti_context AS C ON K.key_id = C.key_id
            JOIN {$CFG->dbprefix}lti_link AS L ON C.context_id = L.context_id
            WHERE K.key_id = :KID AND C.context_id = :CID AND
                L.link_id = :LID
            LIMIT 1";

        $PDOX = LTIX::getConnection();

        $row = $PDOX->rowDie($sql, array(
            ":KID" => $key_id,
            ":CID" => $context_id,
            ":LID" => $link_id)
            );

        if ( ! $row ) {
            Net::send403('Could not locate sourcedid row');
            return;
        }

        $oauth_consumer_key_up = $row['key_key'];
        $oauth_consumer_secret_up = LTIX::decrypt_secret($row['secret']);
        $placementsecret = $row['placementsecret'];
        $settingsstr = $row['settings'];
        try {
            $settings = json_decode($settingsstr);
            $oauth_consumer_key = $settings->key;
            $oauth_consumer_secret = LTIX::decrypt_secret($settings->secret);
        } catch(\Exception $e) {
            Net::send403('Could not parse link settings');
            return;
        }

        $allowRoster = isset($settings->allowRoster) && $settings->allowRoster;
        if ( ! $allowRoster ) {
            Net::send403('Roster sharing not enabled for this context');
            return;
        }

        $sendName = isset($settings->sendName) && $settings->sendName;
        $sendEmail = isset($settings->sendEmail) && $settings->sendEmail;

        $sourcebase = $key_id . '::' . $context_id . '::' . $link_id . '::';
        $plain = $sourcebase . $placementsecret;
        $sig = U::lti_sha256($plain);

        if ( $incoming_sig != $sig ) {
            Net::send403('Invalid sourcedid signature');
            return;
        }

        if ( U::strlen($oauth_consumer_key) < 1 || U::strlen($oauth_consumer_secret) < 1 ) {
            Net::send403("Missing key/secret key=$oauth_consumer_key");
            return;
        }

        $post_key = U::get($_POST, 'oauth_consumer_key');
        if ( $post_key != $oauth_consumer_key ) {
            Net::send403("Mismatch oauth_consumer_key does not match $post_key");
            return;
        }

        $cur_page_url = LTIX::curPageUrl();
        $row_secret = $row['secret'];

        $valid = LTI::verifyKeyAndSecret($post_key,$row_secret,$cur_page_url, $_POST);
        if ( is_array($valid) ) {
            Net::send403($valid[0], $valid[1]);
            return;
        }

        $sql = "SELECT M.role, M.user_id, U.displayname, U.email
            FROM {$CFG->dbprefix}lti_membership AS M
            JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
            WHERE M.context_id = :CID AND M.deleted = 0";

        $rows = $PDOX->allRowsDie($sql, array(":CID" => $context_id));

        header('Content-Type: application/xml; charset=utf-8');
        $this->echoRoster($rows, $sendName, $sendEmail);
    }

    /**
     * A raw XML declaration is a PHP open tag when short_open_tag is on, so print it.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    private function echoRoster(array $rows, $sendName, $sendEmail): void
    {
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n";
        echo "<message_response>\n";
        echo "   <lti_message_type>basic-lis-readmembershipsforcontext</lti_message_type>\n";
        echo "   <members>\n";
        foreach ($rows as $row) {
            $email = $row['email'];
            $displayname = $row['displayname'];
            echo("     <member>\n");
            echo("       <user_id>".$row['user_id']."</user_id>\n");
            if ( $row['role'] >= 1000 ) {
                echo("       <role>Instructor</role>\n");
            } else {
                echo("       <role>Learner</role>\n");
            }
            if ( $sendEmail && is_string($email) && strlen($email) > 0 ) {
                echo("       <person_contact_email_primary>");
                echo(htmlspecialchars($email, ENT_XML1, 'UTF-8'));
                echo("</person_contact_email_primary>\n");
            }
            if ( $sendName && is_string($displayname) && strlen($displayname) > 0 ) {
                echo("       <person_name_full>");
                echo(htmlspecialchars($displayname, ENT_XML1, 'UTF-8'));
                echo("</person_name_full>\n");
            }
            echo("     </member>\n");
        }
        echo "   </members>\n";
        echo "   <statusinfo>\n";
        echo "     <codemajor>Success</codemajor>\n";
        echo "     <codeminor>fullsuccess</codeminor>\n";
        echo "     <severity>Status</severity>\n";
        echo "   </statusinfo>\n";
        echo "</message_response>\n";
    }

}
