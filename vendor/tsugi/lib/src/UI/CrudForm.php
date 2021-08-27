<?php

namespace Tsugi\UI;

use \Tsugi\Util\U;

/**
 * This is a class that supports the creation of simple CRUD forms.
 *
 * This code generates HTML pages and makes SQL queries to automate
 * the creation of simple CRUD forms by passing in values, table names,
 * and strings.  Here is a code example from admin/keys/key-add.php:
 *
 *     $from_location = "keys.php";
 *     $fields = array("key_key", "key_sha256", "secret", "created_at", "updated_at");
 *     CrudForm::insertForm($fields, $from_location);
 *
 * This will output the HTML for a form that the user can fill in and submit.
 * The $from_location is used under the "Cancel" button.
 *
 * The file admin/key/key-add.php is a good example of how to do an insert
 * form and the file admin/key/key-detail.php is a good example of the
 * update case - which is significantly more complex.
 *
 * @TODO This code needs to be able to translate and override the labels
 * on the form.
 */

class CrudForm {

    /**
     * Indicates that CRUD operation was successful
     */
    const CRUD_SUCCESS = 0;
    /**
     * Indicates that CRUD operation failed (likely an SQL problem)
     */
    const CRUD_FAIL = 1;
    /**
     * Indicates that a CRUD operation could not be done because it was missing data.
     */
    const CRUD_NONE = 2;

    /**
     *  Generate the HTML for an insert form.
     *
     * Here is a sample call:
     *
     *     $from_location = "keys.php";
     *     $fields = array("key_key", "key_sha256", "secret", "created_at", "updated_at");
     *     CrudForm::insertForm($fields, $from_location);
     *
     * @param $fields An array of fields to prompt for.
     * @param $from_location A URL to jump to when the user presses 'Cancel'.
     * @param $titles An array of fields->titles
     * @param $fields_defaults An array of fields>default values
     */
    public static function insertForm($fields, $from_location, $titles=false, $fields_defaults=false) {
        echo('<form method="post">'."\n");

        for($i=0; $i < count($fields); $i++ ) {
            $field = $fields[$i];

            // Don't allow setting of these fields
            if ( strpos($field, "_at") > 0 ) continue;
            if ( strpos($field, "_sha256") > 0 ) continue;

            echo('<div class="form-group">'."\n");
            echo('<label for="'.$field.'"><span id="'.$field.'_label">'.self::fieldToTitle($field, $titles)."<span><br/>\n");

            if ( strpos($field, "secret") !== false ) {
                echo('<input id="'.$field.'" type="password" autocomplete="off" size="80" name="'.$field.'"');
                echo("onclick=\"if ( $(this).attr('type') == 'text' ) $(this).attr('type','password'); else $(this).attr('type','text'); return false;\">\n");
            } else {
                echo('<input type="text" size="80" id="'.$field.'" name="'.$field.'"'.(!empty($fields_defaults)?' value="'.htmlent_utf8(self::valueToField($field, $fields_defaults)).'"':'').'>'."\n");
            }
            echo("</label>\n</div>");
        }

        echo('<input type="submit" name="doSave" class="btn btn-normal" value="'._m("Save").'">'."\n");
        echo('<a href="'.$from_location.'" class="btn btn-default">Cancel</a>'."\n");
        echo('</form>'."\n");
    }

    /**
     * Insert data from a $_POST of one of our generated forms insert form into the database.
     *
     * Here is a sample call:
     *
     *     $tablename = "tsugi_lti_key";
     *     $fields = array("key_key", "key_sha256", "secret", "created_at", "updated_at");
     *     CrudForm::handleInsert($tablename, $fields);
     *
     * @param $fields An array of fields to be inserted.  These items must be
     * in the $_POST data as well.
     * @return int Returns the constant for SUCCESS, FAIL, or NONE
     */
    public static function handleInsert($tablename, $fields) {
        global $PDOX;
        if ( isset($_POST['doSave']) && count($_POST) > 0 ) {
            $names = '';
            $values = '';
            $parms = array();
            for($i=0; $i < count($fields); $i++ ) {
                $field = $fields[$i];

                if ( strlen($names) > 0 ) $names .= ', ';
                if ( strlen($values) > 0 ) $values .= ', ';

                $names .= $field;
                if ( strpos($field, "_at") > 0 ) {
                    $values .= "NOW()";
                    continue;
                }

                $key = $field;

                // Set the sha256 value if it is not there
                if ( strpos($field, "_sha256") !== false && ! isset($_POST[$field])) {
                    $key = str_replace("_sha256", "_key", $field);
                    // Nulls allowed
                    if ( !array_key_exists($key, $_POST) ) {
                        $_SESSION['error'] = "Missing POST field: ".$key;
                        return self::CRUD_FAIL;
                    }
                    if ( $_POST[$key] === null ) {
                        $value = null;
                    } else {
                        $value = lti_sha256($_POST[$key]);
                    }
                } else {
                    // Nulls allowed
                    if ( array_key_exists($key, $_POST) ) {
                        $value = $_POST[$field];
                    } else {
                        $_SESSION['error'] = "Missing POST field: ".$field;
                        return self::CRUD_FAIL;
                    }
                }
                $parms[':'.$i] = $value;
                $values .= ":".$i;
            }
            $sql = "INSERT IGNORE INTO $tablename \n( $names ) VALUES ( $values )";
            $stmt = $PDOX->queryDie($sql, $parms);
            if ( $stmt->rowCount() < 1 ) {
                $_SESSION['error'] = "Could not insert record: duplicate key";
                return self::CRUD_FAIL;
            }
            error_log('Row count '.$stmt->rowCount());
            $_SESSION['success'] = _m("Record Inserted");
            return self::CRUD_SUCCESS;
        }
        return self::CRUD_NONE;
    }

    /**
     *  Generate the HTML for an update form.
     *
     * Here is a sample call:
     *
     *     $from_location = "keys.php";
     *     $fields = array("key_key", "key_sha256", "secret", "created_at", "updated_at");
     *     $current = getCurrentFileUrl(__FILE__);
     *     $retval = CrudForm::updateForm($row, $fields, $current, $from_location, true, true);
     *
     * @param $row The existing data for the fields.
     * @param $fields An array of fields to be shown.
     * @param $current The URL of the current HTML page.
     * @param $from_location A URL to jump to when the user presses 'Cancel'.
     * @param $allow_edit True/false as to whether to show an Edit button
     * @param $allow_delete True/false as to whether to show a Delete button
     * @param $extra_buttons An array of additional buttons to show
     * @param $titles An array of fields->titles
     */
    public static function updateForm($row, $fields, $current, $from_location,
        $allow_edit=false, $allow_delete=false, $extra_buttons=false, $titles=false)
    {
        $key = $fields['0'];
        if ( !isset($_REQUEST[$key]) ) {
            return "Required $key= parameter";
        }
        $key_value = $_REQUEST[$key] + 0;
        $do_edit = isset($_REQUEST['edit']) && $_REQUEST['edit'] == 'yes';

        echo('<form method="post">'."\n");
        if ( is_string($from_location) ) echo('<a href="'.$from_location.'" class="btn btn-default">'._m('Exit').'</a>'."\n");
        if ( $allow_edit ) {
            if ( $do_edit ) {
                echo('<input type="submit" name="doUpdate" class="btn btn-normal" value="'._m("Update").'">'."\n");
                echo('<a href="'.$current.'?'.$key.'='.$key_value.'" class="btn btn-success">'._m("Cancel Edit").'</a>'."\n");
            } else {
                echo('<a href="'.$current.'?'.$key.'='.$key_value.'&edit=yes" class="btn btn-warning">'._m("Edit").'</a>'."\n");
            }
        }
        if ( is_array($extra_buttons) ) {
            foreach($extra_buttons as $button_text => $button_url ) {
                if ( is_string($button_url) && strpos($button_url, "<a href=") === 0 ) {
                    echo($button_url."\n");
                } else {
                    echo('<a href="'.$button_url.'" class="btn btn-success">'._m($button_text).'</a>'."\n");
                }
            }
        }
        if ( $allow_delete ) {
            echo('<input type="hidden" name="'.$key.'" value="'.$key_value.'">'."\n");
            echo('<input type="submit" name="doDelete" class="btn btn-danger" value="'._m("Delete").'"');
            echo(" onclick=\"return confirm('Are you sure you want to delete this record?');\">\n");
        }

        echo("<p>\n");

        for($i=0; $i < count($fields); $i++ ) {
            $field = $fields[$i];
            $value = $row[$field];
            if ( ! $do_edit ) {
                echo('<p><strong><span id="'.$field.'_label">'.self::fieldToTitle($field, $titles)."</span></strong></p>\n");
                if ( strpos($field, "secret") !== false || strpos($field, "privkey") !== false ) {
                    echo('<p id="'.$field.'">'."\n");
                    echo("<span style=\"display: none;\" id=\"text_{$i}\">".htmlent_utf8($value).'</span>');
                    echo("<span id=\"show_{$i}\" onclick=\"$('#text_{$i}').show();$('#show_{$i}').hide();$('#hide_{$i}').show();\";>(Click to show)</span>\n");
                    echo("<span id=\"hide_{$i}\" onclick=\"$('#text_{$i}').hide();$('#hide_{$i}').hide();$('#show_{$i}').show();\" style=\"display:none\";>(Click to hide)</span>\n");
                    echo("\n</p>\n");
                } else {
                    echo('<p id="'.$field.'">'.htmlent_utf8($value)."</p>\n");
                }
                continue;
            }

            if ( $i == 0 && strpos($field,"_id") !== false ) {
                echo('<input type="hidden" name="'.$field.'" value="'.htmlent_utf8($value).'">'."\n");
                continue;
            }

            // Don't allow explicit updating of these fields
            if ( strpos($field, "_at") > 0 ) continue;

            echo('<div class="form-group">'."\n");
            echo('<label for="'.$field.'">'.self::fieldToTitle($field, $titles)."<br/>\n");
            if ( isset($_POST[$field]) ) {
                $value = $_POST[$field];
            }

            if ( strpos($field, "secret") !== false ) {
                echo('<input id="'.$field.'" type="password" autocomplete="off" size="80" name="'.$field.'" value="'.
                        htmlent_utf8($value).'"');
                echo("onclick=\"if ( $(this).attr('type') == 'text' ) $(this).attr('type','password'); else $(this).attr('type','text'); return false;\">\n");
            } else if ( strlen($value) > 60 ) {
                echo('<textarea rows="10" cols="70" id="'.$field.'" name="'.$field.'">'.htmlent_utf8($value).'</textarea>'."\n");
            } else {
                echo('<input type="text" size="80" id="'.$field.'" name="'.$field.'" value="'.htmlent_utf8($value).'">'."\n");
            }
            echo("</label>\n</div>");
        }
        echo('</form>'."\n");
        return true;
    }

    /**
     * Apply the results of an update form to the database
     *
     * Here is a sample call:
     *
     *     $tablename = "tsugi_lti_key";
     *     $fields = array("key_id", "key_key", "secret", "created_at", "updated_at");
     *     $where_clause .= "user_id = :UID";
     *     $query_fields = array(":UID" => $_SESSION['id']);
     *     $row =  CrudForm::handleUpdate($tablename, $fields, $where_clause, $query_fields, true, true);
     *
     * This code very much depends on the $_POST data being generated from the
     * form that this class created.   For example it decides to delete or update
     * based on a $_POST field from the button that was pushed.  Also the
     * primary key comes from the $_POST data, so this routine checks for
     * consistency and provides a WHERE clause capability to make sure folks
     * can only update data that belongs to them.
     *
     * Also this code depends on database column naming conventions -
     * in particular it knows that key_id is a primary key. In the above
     * example, the ultimate WHERE clause will effectively be as follows:
     *
     *     UPDATE ... WHERE key_id = $_POST['key_id'] AND user_id = $_SESSION['id']
     *
     * This way, even if the user forges the key_id data to be one that does
     * not belong to them, the AND clause will stop the UPDATE from happening.
     * If this is an administrator that can update any record - simply set
     * the $where_clause to an empty string and $query_fields to an empty
     * array.
     *
     * If we were editing some context-wide data as instructor, we might add
     * the current context_id of the logged in instructor to the WHERE clause.
     *
     * @param $fields An array of fields to be updated.  These items must be
     * in the $_POST data as well.  The primary key should be the first field
     * in the list and end in "_id".
     * @param $where_clause An optional (can be an empty string) WHERE clause limiting
     * which primary keys can be updated.
     * @param $query_parms If there is a where clause, this is an associative array
     * providing the values for the substitutable items in the WHERE clause.
     * @param $allow_edit True/false as to whether editing is allowed
     * @param $allow_delete True/false as to whether deleting is allowed
     * @return int Returns the constant for SUCCESS, FAIL, or NONE
     */
    public static function handleUpdate($tablename, $fields, $where_clause=false,
        $query_parms=array(), $allow_edit=false, $allow_delete=false)
    {
        global $PDOX;
        $key = $fields['0'];

        if ( !isset($_REQUEST[$key]) ) {
            $_SESSION['error'] = "Required $key= parameter";
            return self::CRUD_FAIL;
        }

        // Inner WHERE clause
        $key_value = $_REQUEST[$key] + 0;
        if ( $where_clause === false || strlen($where_clause) < 1 ) {
            $where_clause = "$key = :KID";
        } else {
            $where_clause = "( ".$where_clause." ) AND $key = :KID";
        }
        $query_parms[":KID"] = $key_value;

        $do_edit = isset($_REQUEST['edit']) && $_REQUEST['edit'] == 'yes';

        $sql = CrudForm::selectSql($tablename, $fields, $where_clause);
        $row = $PDOX->rowDie($sql, $query_parms);
        if ( $row === false ) {
            $_SESSION['error'] = "Unable to retrieve row";
            return self::CRUD_FAIL;
        }

        // We know we are OK because we already retrieved the row
        if ( $allow_delete && isset($_POST['doDelete']) ) {
            $sql = "DELETE FROM $tablename WHERE $where_clause";
            $stmt = $PDOX->queryDie($sql, $query_parms);
            $_SESSION['success'] = _m("Record deleted");
            return self::CRUD_SUCCESS;
        }

        // The update
        if ( $allow_edit && $do_edit && isset($_POST['doUpdate']) && count($_POST) > 0 ) {
            $set = '';
            $parms = $query_parms;
            for($i=0; $i < count($fields); $i++ ) {
                $field = $fields[$i];
                if ( $i == 0 && strpos($field, "_id") > 0 ) continue;
                if ( $field != 'updated_at' && strpos($field, "_at") > 0 ) continue;

                if ( strlen($set) > 0 ) $set .= ', ';
                if ( $field == 'updated_at' ) {
                    $set .= $field."= NOW()";
                    continue;
                }

                // Update the sha256 value if we have a corresponding _key value
                if ( strpos($field, "_sha256") !== false && ! isset($_POST[$field])) {
                    $key = str_replace("_sha256", "_key", $field);
                    // Allow nulls
                    if ( ! array_key_exists($key, $_POST) ) continue;
                    if ( $_POST[$key] === null ) {
                        $value = null;
                    } else {
                        $value = lti_sha256($_POST[$key]);
                    }
                    $set .= $field."= :".$i;
                    $parms[':'.$i] = $value;
                    continue;
                }

                // Nulls allowed
                if ( !array_key_exists($field, $_POST) ) {
                    $_SESSION['error'] = _m("Missing POST field: ").$field;
                    return self::CRUD_FAIL;
                }
                $set .= $field."= :".$i;
                $parms[':'.$i] = $_POST[$field];
            }
            $sql = "UPDATE $tablename SET $set WHERE $where_clause";
            $stmt = $PDOX->queryDie($sql, $parms);
            $_SESSION['success'] = "Record Updated";
            return self::CRUD_SUCCESS;
        }
        return $row;
    }

    /**
     * Maps a field name to a presentable title.
     *
     * @todo Make this translatable and pretty
     */
    public static function fieldToTitle($name, $titles=false) {
        if ( is_array($titles) && U::get($titles, $name) ) return U::get($titles, $name);
        return ucwords(str_replace('_',' ',$name));
    }

    /**
     * Maps a default value to a field.
     *
     * @return string
     */
    public static function valueToField($name, $values=false) {
        if ( is_array($values) && U::get($values, $name) ) return U::get($values, $name);
        return '';
    }

    /**
     * Produce the SELECT statement for a table, set of fields and where clause.
     *
     * @param $fields An array of field names to select.
     * @param $where_clause This is the WHERE clause but should not include
     * the WHERE keyword.
     */
    public static function selectSql($tablename, $fields, $where_clause=false) {
        $sql = "SELECT ";
        $first = true;
        foreach ( $fields as $field ) {
            if ( ! $first ) $sql .= ', ';
            $sql .= $field;
            $first = false;
        }
        $sql .= "\n FROM ".$tablename;
        if ( $where_clause && strlen($where_clause) > 0 ) $sql .= "\nWHERE ".$where_clause;
        return $sql;
    }


}
