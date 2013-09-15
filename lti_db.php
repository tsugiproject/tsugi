<?php 

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

// Returns as much as we have in all the tables
// Assume..  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
function checkKey($db, $p, $profile_table, $post) {
	$sql = "SELECT k.key_id, k.secret, c.context_id, c.title AS context_title, 
		l.link_id, l.title AS link_title, 
		u.user_id, u.displayname AS user_displayname, u.email AS user_email,
		m.membership_id, m.role\n";

	if ( $profile_table ) {
		$sql .= ", p.profile_id, p.displayname AS profile_displayname, p.email AS profile_email\n";
	}

	if ( $post['service'] && $post['sourcedid'] ) {
		$sql .= ", s.service_id, r.result_id, r.sourcedid\n";
	}

	$sql .="
		FROM {$p}lti_key AS k 
		LEFT JOIN {$p}lti_context AS c ON k.key_id = c.key_id AND c.context_sha256 = :context 
		LEFT JOIN {$p}lti_link AS l ON c.context_id = l.context_id AND l.link_sha256 = :link
		LEFT JOIN {$p}lti_user AS u ON k.key_id = u.key_id AND u.user_sha256 = :user
		LEFT JOIN {$p}lti_membership AS m ON u.user_id = m.user_id AND c.context_id = m.context_id\n";

	if ( $profile_table ) {
		$sql .= "LEFT JOIN {$profile_table} AS p ON u.user_id = p.user_id\n";
	}

	if ( $post['service'] && $post['sourcedid'] ) {
		$sql .= "LEFT JOIN {$p}lti_service AS s ON k.key_id = s.key_id AND s.service_sha256 = :service 
				LEFT JOIN {$p}lti_result AS r ON u.user_id = r.user_id AND l.link_id = r.link_id AND 
						s.service_id = r.service_id AND r.sourcedid_sha256 = :sourcedid\n";
	}
	$sql .= "WHERE k.key_sha256 = :key LIMIT 1\n";
	
	echo($sql);
	
	$stmt = $db->prepare($sql);
	$parms = array(
		':key' => lti_sha256($post['key']),
		':context' => lti_sha256($post['context_id']),
		':link' => lti_sha256($post['link_id']), 
		':user' => lti_sha256($post['user_id']));
	
	if ( $post['service'] && $post['sourcedid'] ) {
		$parms[':service'] = lti_sha256($post['service']);
		$parms[':sourcedid'] = lti_sha256($post['sourcedid']);
	}
	
	$stmt->execute($parms);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($row);
	return $row;
}

// Insert the missing bits...
function insertNew(&$row, $db, $p, $post) {
	// Unescape each time we use this stuff
	$FIXED = array();
	foreach($_POST as $key => $value ) {
		if (get_magic_quotes_gpc()) $value = stripslashes($value);
		$FIXED[$key] = $value;
	}

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
		echo("=== Inserted context id=".$row['context_id']." ".$row['context_title']."\n");
	}
	
	if ( $row['link_id'] === null && isset($FIXED['resource_link_id']) ) {
		$sql = "INSERT INTO {$p}lti_link 
			( link_key, link_sha256, title, context_id, created_at, updated_at ) VALUES
				( :link_key, :link_sha256, :title, :context_id, NOW(), NOW() )";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':link_key' => $FIXED['resource_link_id'],
			':link_sha256' => lti_sha256($FIXED['resource_link_id']),
			':title' => $post['link_title'],
			':context_id' => $row['context_id']));
		$row['link_id'] = $db->lastInsertId();
		$row['link_title'] = $post['link_title'];
		echo("=== Inserted link id=".$row['link_id']." ".$row['link_title']."\n");
	}
	
	if ( $row['user_id'] === null && isset($FIXED['user_id']) ) {
		$sql = "INSERT INTO {$p}lti_user 
			( user_key, user_sha256, displayname, email, key_id, created_at, updated_at ) VALUES
			( :user_key, :user_sha256, :displayname, :email, :key_id, NOW(), NOW() )";
		$stmt = $db->prepare($sql);
		$stmt->execute(array(
			':user_key' => $FIXED['user_id'],
			':user_sha256' => lti_sha256($FIXED['user_id']),
			':displayname' => $post['user_displayname'],
			':email' => $post['user_email'],
			':key_id' => $row['key_id']));
		$row['user_id'] = $db->lastInsertId();
		$row['user_email'] = $post['user_email'];
		$row['user_displayname'] = $post['user_displayname'];
		echo("=== Inserted user id=".$row['user_id']." ".$row['user_email']."\n");
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
		echo("=== Inserted membership id=".$row['membership_id']." role=".$row['role'].
			" user=".$row['user_id']." context=".$row['context_id']."\n");
	}
	
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
		echo("=== Inserted service id=".$row['service_id']." ".$post['service']."\n");
	}
	
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
		echo("=== Inserted result id=".$row['result_id']." service=".$row['service_id']." ".$post['sourcedid']."\n");
	}
}

?>
