<?php 

require_once 'lib/OAuth.php';

// Convienence method to wrap sha256
function lti_sha256($val) {
	return hash('sha256', $val);
}

// Extract info from $_POST applying our business rules and using our
// naming conventions
function extractPost() {
	// Unescape each time we use this stuff
	$FIXED = array();
	foreach($_POST as $key => $value ) {
		if (get_magic_quotes_gpc()) $value = stripslashes($value);
		$FIXED[$key] = $value;
	}
	$retval = array();
	$retval['key'] = isset($FIXED['oauth_consumer_key']) ? $FIXED['oauth_consumer_key'] : null;
	$retval['context_id'] = isset($FIXED['context_id']) ? $FIXED['context_id'] : null;
	$retval['link_id'] = isset($FIXED['resource_link_id']) ? $FIXED['resource_link_id'] : null;
	$retval['user_id'] = isset($FIXED['user_id']) ? $FIXED['user_id'] : null;

	if ( $retval['key'] && $retval['context_id'] && $retval['link_id']  && $retval['user_id'] ) {
		// OK To Continue
	} else {
		return false;
	}
	
	$retval['service'] = isset($FIXED['lis_outcome_service_url']) ? $FIXED['lis_outcome_service_url'] : null;
	$retval['sourcedid'] = isset($FIXED['lis_result_sourcedid']) ? $FIXED['lis_result_sourcedid'] : null;

	$retval['context_title'] = isset($FIXED['context_title']) ? $FIXED['context_title'] : null;
	$retval['link_title'] = isset($FIXED['resource_link_title']) ? $FIXED['resource_link_title'] : null;
	$retval['user_email'] = isset($FIXED['lis_person_contact_email_primary']) ? $FIXED['lis_person_contact_email_primary'] : null;
	if ( isset($FIXED['lis_person_name_full']) ) {
		$retval['user_displayname'] = $FIXED['lis_person_name_full'];
	} else if ( isset($FIXED['lis_person_name_given']) && isset($FIXED['lis_person_name_family']) ) {
		$retval['user_displayname'] = $FIXED['lis_person_name_given'].' '.$FIXED['lis_person_name_family'];
	} else if ( isset($FIXED['lis_person_name_given']) ) {
		$retval['user_displayname'] = $FIXED['lis_person_name_given'];
	} else if ( isset($FIXED['lis_person_name_family']) ) {
		$retval['user_displayname'] = $FIXED['lis_person_name_given'];
	}
	$retval['role'] = 0;
	if ( isset($FIXED['roles']) ) {
        $roles = strtolower($FIXED['roles']);
        if ( ! ( strpos($roles,"instructor") === false ) ) $retval['role'] = 1;
        if ( ! ( strpos($roles,"administrator") === false ) ) $retval['role'] = 1;
	}
	return $retval;
}

function getCompositeKey($post, $secret) {
	$comp = $secret .'::'. $post['key'] .'::'. $post['context_id'] .'::'. 
		$post['link_id']  .'::'. $post['user_id'] .'::' . $_SERVER['HTTP_USER_AGENT'];
	return md5($comp);
}

// Returns as much as we have in all the tables
// Assume..  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
function loadAllData($db, $p, $profile_table, $post) {
	$errormode = $db->getAttribute(PDO::ATTR_ERRMODE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$sql = "SELECT k.key_id, k.key_key, k.secret, c.context_id, c.title AS context_title, 
		l.link_id, l.title AS link_title, 
		u.user_id, u.displayname AS user_displayname, u.email AS user_email,
		m.membership_id, m.role";

	if ( $profile_table ) {
		$sql .= ",
		p.profile_id, p.displayname AS profile_displayname, p.email AS profile_email";
	}

	if ( $post['service'] ) {
		$sql .= ",
		s.service_id, s.service_key AS service";
	}

	if ( $post['sourcedid'] ) {
		$sql .= ",
		r.result_id, r.sourcedid";
	}

	$sql .="\nFROM {$p}lti_key AS k 
		LEFT JOIN {$p}lti_context AS c ON k.key_id = c.key_id AND c.context_sha256 = :context 
		LEFT JOIN {$p}lti_link AS l ON c.context_id = l.context_id AND l.link_sha256 = :link
		LEFT JOIN {$p}lti_user AS u ON k.key_id = u.key_id AND u.user_sha256 = :user
		LEFT JOIN {$p}lti_membership AS m ON u.user_id = m.user_id AND c.context_id = m.context_id";

	if ( $profile_table ) {
		$sql .= "
		LEFT JOIN {$profile_table} AS p ON u.profile_id = p.profile_id";
	}

	if ( $post['service'] ) {
		$sql .= "
		LEFT JOIN {$p}lti_service AS s ON k.key_id = s.key_id AND s.service_sha256 = :service";
	}
	if ( $post['sourcedid'] ) {
		$sql .= "
		LEFT JOIN {$p}lti_result AS r ON u.user_id = r.user_id AND l.link_id = r.link_id AND 
			r.sourcedid_sha256 = :sourcedid";
	}
	$sql .= "\nWHERE k.key_sha256 = :key LIMIT 1\n";
	
	// echo($sql);
	$stmt = $db->prepare($sql);
	$parms = array(
		':key' => lti_sha256($post['key']),
		':context' => lti_sha256($post['context_id']),
		':link' => lti_sha256($post['link_id']), 
		':user' => lti_sha256($post['user_id']));
	
	if ( $post['service'] ) {
		$parms[':service'] = lti_sha256($post['service']);
	}

	if ( $post['sourcedid'] ) {
		$parms[':sourcedid'] = lti_sha256($post['sourcedid']);
	}
	
	$stmt->execute($parms);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	// Restore ERRMODE
	$db->setAttribute(PDO::ATTR_ERRMODE, $errormode);
	return $row;
}

// Insert the missing bits and update the new bits...
// TODO: Contemplate the deep mystery of transactions here
function adjustData($db, $p, &$row, $post) {
	$errormode = $db->getAttribute(PDO::ATTR_ERRMODE);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$actions = array();
	if ( $row['context_id'] === null) {
		$sql = "INSERT INTO {$p}lti_context 
			( context_key, context_sha256, title, key_id, created_at, updated_at ) VALUES
			( :context_key, :context_sha256, :title, :key_id, NOW(), NOW() )";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':context_key' => $post['context_id'],
			':context_sha256' => lti_sha256($post['context_id']),
			':title' => $post['context_title'],
			':key_id' => $row['key_id']));
		$row['context_id'] = $db->lastInsertId();
		$row['context_title'] = $post['context_title'];
		$actions[] = "=== Inserted context id=".$row['context_id']." ".$row['context_title'];
	}
	
	if ( $row['link_id'] === null && isset($post['link_id']) ) {
		$sql = "INSERT INTO {$p}lti_link 
			( link_key, link_sha256, title, context_id, created_at, updated_at ) VALUES
				( :link_key, :link_sha256, :title, :context_id, NOW(), NOW() )";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':link_key' => $post['link_id'],
			':link_sha256' => lti_sha256($post['link_id']),
			':title' => $post['link_title'],
			':context_id' => $row['context_id']));
		$row['link_id'] = $db->lastInsertId();
		$row['link_title'] = $post['link_title'];
		$actions[] = "=== Inserted link id=".$row['link_id']." ".$row['link_title'];
	}
	
	if ( $row['user_id'] === null && isset($post['user_id']) ) {
		$sql = "INSERT INTO {$p}lti_user 
			( user_key, user_sha256, displayname, email, key_id, created_at, updated_at ) VALUES
			( :user_key, :user_sha256, :displayname, :email, :key_id, NOW(), NOW() )";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':user_key' => $post['user_id'],
			':user_sha256' => lti_sha256($post['user_id']),
			':displayname' => $post['user_displayname'],
			':email' => $post['user_email'],
			':key_id' => $row['key_id']));
		$row['user_id'] = $db->lastInsertId();
		$row['user_email'] = $post['user_email'];
		$row['user_displayname'] = $post['user_displayname'];
		$actions[] = "=== Inserted user id=".$row['user_id']." ".$row['user_email'];
	}
	
	if ( $row['membership_id'] === null && $row['context_id'] !== null && $row['user_id'] !== null ) {
		$sql = "INSERT INTO {$p}lti_membership 
			( context_id, user_id, role, created_at, updated_at ) VALUES
			( :context_id, :user_id, :role, NOW(), NOW() )";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':context_id' => $row['context_id'],
			':user_id' => $row['user_id'],
			':role' => $post['role']));
		$row['membership_id'] = $db->lastInsertId();
		$row['role'] = $post['role'];
		$actions[] = "=== Inserted membership id=".$row['membership_id']." role=".$row['role'].
			" user=".$row['user_id']." context=".$row['context_id'];
	}

	// We need to handle the case where the service URL changes but we already have a sourcedid
	$oldserviceid = $row['service_id'];
	if ( $row['service_id'] === null && $post['service'] && $post['sourcedid'] ) {
		$sql = "INSERT INTO {$p}lti_service 
			( service_key, service_sha256, key_id, created_at, updated_at ) VALUES
			( :service_key, :service_sha256, :key_id, NOW(), NOW() )";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':service_key' => $post['service'],
			':service_sha256' => lti_sha256($post['service']),
			':key_id' => $row['key_id']));
		$row['service_id'] = $db->lastInsertId();
		$row['service'] = $post['service'];
		$actions[] = "=== Inserted service id=".$row['service_id']." ".$post['service'];
	}

	// If we just created a new service entry but we already had a result entry, update it
	if ( $oldserviceid === null && $row['result_id'] !== null && $row['service_id'] !== null && $post['service'] && $post['sourcedid'] ) {
		$sql = "UPDATE {$p}lti_result SET service_id = :service_id WHERE result_id = :result_id";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':service_id' => $row['service_id'],
			':result_id' => $row['result_id']));
		$actions[] = "=== Updated result id=".$row['result_id']." service=".$row['service_id']." ".$post['sourcedid'];
	}
	
	// If we don'have a result but do have a service - link them together
	if ( $row['result_id'] === null && $row['service_id'] !== null && $post['service'] && $post['sourcedid'] ) {
		$sql = "INSERT INTO {$p}lti_result 
			( sourcedid, sourcedid_sha256, service_id, link_id, user_id, created_at, updated_at ) VALUES
			( :sourcedid, :sourcedid_sha256, :service_id, :link_id, :user_id, NOW(), NOW() )";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':sourcedid' => $post['sourcedid'],
			':sourcedid_sha256' => lti_sha256($post['sourcedid']),
			':service_id' => $row['service_id'],
			':link_id' => $row['link_id'],
			':user_id' => $row['user_id']));
		$row['result_id'] = $db->lastInsertId();
		$row['sourcedid'] = $post['sourcedid'];
		$actions[] = "=== Inserted result id=".$row['result_id']." service=".$row['service_id']." ".$post['sourcedid'];
	}

	// If we don'have a result and do not have a service - just store the result (prep for LTI 2.0)
	if ( $row['result_id'] === null && $row['service_id'] === null && ! $post['service'] && $post['sourcedid'] ) {
		$sql = "INSERT INTO {$p}lti_result 
			( sourcedid, sourcedid_sha256, link_id, user_id, created_at, updated_at ) VALUES
			( :sourcedid, :sourcedid_sha256, :link_id, :user_id, NOW(), NOW() )";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':sourcedid' => $post['sourcedid'],
			':sourcedid_sha256' => lti_sha256($post['sourcedid']),
			':link_id' => $row['link_id'],
			':user_id' => $row['user_id']));
		$row['result_id'] = $db->lastInsertId();
		$actions[] = "=== Inserted LTI 2.0 result id=".$row['result_id']." service=".$row['service_id']." ".$post['sourcedid'];
	}

	// Here we handle updates to context_title, link_title, user_displayname, user_email, or role
	if ( $post['context_title'] != $row['context_title'] ) {
		$stmt = $db->prepare("UPDATE {$p}lti_context SET title = :title WHERE context_id = :context_id");
		$stmt->execute(array(
			':title' => $post['context_title'],
			':context_id' => $row['context_id']));
		$row['context_title'] = $post['context_title'];
		$actions[] = "=== Updated context=".$row['context_id']." title=".$post['context_title'];
	}

	if ( $post['link_title'] != $row['link_title'] ) {
		$stmt = $db->prepare("UPDATE {$p}lti_link SET title = :title WHERE link_id = :link_id");
		$stmt->execute(array(
			':title' => $post['link_title'],
			':link_id' => $row['link_id']));
		$row['link_title'] = $post['link_title'];
		$actions[] = "=== Updated link=".$row['link_id']." title=".$post['link_title'];
	}

	if ( $post['user_displayname'] != $row['user_displayname'] ) {
		$stmt = $db->prepare("UPDATE {$p}lti_user SET displayname = :displayname WHERE user_id = :user_id");
		$stmt->execute(array(
			':displayname' => $post['user_displayname'],
			':user_id' => $row['user_id']));
		$row['user_displayname'] = $post['user_displayname'];
		$actions[] = "=== Updated user=".$row['user_id']." displayname=".$post['user_displayname'];
	}

	if ( $post['user_email'] != $row['user_email'] ) {
		$stmt = $db->prepare("UPDATE {$p}lti_user SET email = :email WHERE user_id = :user_id");
		$stmt->execute(array(
			':email' => $post['user_email'],
			':user_id' => $row['user_id']));
		$row['user_email'] = $post['user_email'];
		$actions[] = "=== Updated user=".$row['user_id']." email=".$post['user_email'];
	}

	if ( $post['role'] != $row['role'] ) {
		$stmt = $db->prepare("UPDATE {$p}lti_membership SET role = :role WHERE membership_id = :membership_id");
		$stmt->execute(array(
			':role' => $post['role'],
			':membership_id' => $row['membership_id']));
		$row['role'] = $post['role'];
		$actions[] = "=== Updated membership=".$row['membership_id']." role=".$post['role'];
	}

	// Restore ERRMODE
	$db->setAttribute(PDO::ATTR_ERRMODE, $errormode);
    foreach ($actions as $action) {
        echo($action);
    }
	return $actions;
}

// Verify the message signature
function verifyKeyAndSecret($key, $secret) {
	if ( ! ($key && $secret) ) return array("Missing key or secret", "");
	$store = new DbTrivialOAuthDataStore();
	$store->add_consumer($key, $secret);

	$server = new OAuthServer($store);

	$method = new OAuthSignatureMethod_HMAC_SHA1();
	$server->add_signature_method($method);
	$request = OAuthRequest::from_request();

	$basestring = $request->get_signature_base_string();

	try {
		$server->verify_request($request);
		return true;
	} catch (Exception $e) {
		return array($e->getMessage(), $basestring);;
	}
}

/**
 * A Trivial memory-based store - no support for tokens
 */
class DbTrivialOAuthDataStore extends OAuthDataStore {
    private $consumers = array();

    function add_consumer($consumer_key, $consumer_secret) {
        $this->consumers[$consumer_key] = $consumer_secret;
    }

    function lookup_consumer($consumer_key) {
        if ( strpos($consumer_key, "http://" ) === 0 ) {
            $consumer = new OAuthConsumer($consumer_key,"secret", NULL);
            return $consumer;
        }
        if ( $this->consumers[$consumer_key] ) {
            $consumer = new OAuthConsumer($consumer_key,$this->consumers[$consumer_key], NULL);
            return $consumer;
        }
        return NULL;
    }

    function lookup_token($consumer, $token_type, $token) {
        return new OAuthToken($consumer, "");
    }

    // Return NULL if the nonce has not been used
    // Return $nonce if the nonce was previously used
    function lookup_nonce($consumer, $token, $nonce, $timestamp) {
        // Should add some clever logic to keep nonces from
        // being reused - for no we are really trusting
		// that the timestamp will save us
        return NULL;
    }

    function new_request_token($consumer) {
        return NULL;
    }

    function new_access_token($token, $consumer) {
        return NULL;
    }
}
?>
