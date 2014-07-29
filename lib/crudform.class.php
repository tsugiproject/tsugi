<?php

namespace Tsugi;

class CRUDForm {

    const UPDATE_SUCCESS = 0;
    const UPDATE_FAIL = 1;
    const UPDATE_NONE = 2;

    const INSERT_SUCCESS = 0;
    const INSERT_FAIL = 1;
    const INSERT_NONE = 2;

    public static function fieldToTitle($name) {
        return ucwords(str_replace('_',' ',$name));
    }

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

    public static function updateHandle($tablename, $fields, $query_parms=array(),
        $where_clause=false, $allow_edit=false, $allow_delete=false)
    {
        global $PDOX;
        $key = $fields['0'];

        if ( !isset($_REQUEST[$key]) ) {
            $_SESSION['error'] = "Required $key= parameter";
            return self::UPDATE_FAIL;
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

        $sql = crudSelectSql($tablename, $fields, $where_clause);
        $row = $PDOX->rowDie($sql, $query_parms);
        if ( $row === false ) {
            $_SESSION['error'] = "Unable to retrieve row";
            return self::UPDATE_FAIL;
        }

        // We know we are OK because we already retrieved the row
        if ( $allow_delete && isset($_POST['doDelete']) ) {
            $sql = "DELETE FROM $tablename WHERE $where_clause";
            $stmt = $PDOX->queryDie($sql, $query_parms);
            $_SESSION['success'] = _m("Record deleted");
            return self::UPDATE_SUCCESS;
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
                if ( !isset($_POST[$field]) ) {
                    $_SESSION['error'] = _m("Missing POST field: ").$field;
                    return self::UPDATE_FAIL;
                }
                $set .= $field."= :".$i;
                $parms[':'.$i] = $_POST[$field];
            }
            $sql = "UPDATE $tablename SET $set WHERE $where_clause";
            $stmt = $PDOX->queryDie($sql, $parms);
            $_SESSION['success'] = "Record Updated";
            return self::UPDATE_SUCCESS;
        }
        return $row;
    }

    public static function updateForm($row, $fields, $current, $from_location, $allow_edit=false, $allow_delete=false)
    {
        $key = $fields['0'];
        if ( !isset($_REQUEST[$key]) ) {
            return "Required $key= parameter";
        }
        $key_value = $_REQUEST[$key] + 0;
        $do_edit = isset($_REQUEST['edit']) && $_REQUEST['edit'] == 'yes';

        echo('<form method="post">'."\n");
        echo('<a href="'.$from_location.'" class="btn btn-default">Done</a>'."\n");
        if ( $allow_edit ) {
            if ( $do_edit ) {
                echo('<a href="'.$current.'?'.$key.'='.$key_value.'" class="btn btn-success">'._m("Cancel Edit").'</a>'."\n");
            } else {
                echo('<a href="'.$current.'?'.$key.'='.$key_value.'&edit=yes" class="btn btn-warning">'._m("Edit").'</a>'."\n");
            }
        }
        if ( $allow_delete ) {
            echo('<input type="hidden" name="'.$key.'" value="'.$key_value.'">'."\n");
            echo('<input type="submit" name="doDelete" class="btn btn-danger" value="'._m("Delete").'"');
            echo(" onclick=\"return confirm('Are you sure you want to delete this record?');\">\n");
        }
        echo("</form>\n");

        echo("<p>\n");
        if ( $do_edit ) echo('<form method="post">'."\n");

        for($i=0; $i < count($fields); $i++ ) {
            $field = $fields[$i];
            $value = $row[$field];
            if ( ! $do_edit ) {
                echo('<p><strong>'.self::fieldToTitle($field)."</strong></p>\n");
                if ( strpos($field, "secret") !== false ) {
                    echo("<p onclick=\"$('#stars_{$i}').toggle();$('#text_{$i}').toggle();\">\n");
                    echo("<span id=\"stars_{$i}\">***********</span>\n");
                    echo("<span style=\"display: none;\" id=\"text_{$i}\">".htmlent_utf8($value).'</span>');
                    echo("\n</p>\n");
                } else {
                    echo("<p>".htmlent_utf8($value)."</p>\n");
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
            echo('<label for="'.$field.'">'.self::fieldToTitle($field)."<br/>\n");
            if ( isset($_POST[$field]) ) {
                $value = $_POST[$field];
            }

            if ( strpos($field, "secret") !== false ) {
                echo('<input id="'.$field.'" type="password" size="80" name="'.$field.'" value="'.
                        htmlent_utf8($value).'"');
                echo("onclick=\"if ( $(this).attr('type') == 'text' ) $(this).attr('type','password'); else $(this).attr('type','text'); return false;\">\n");
            } else if ( strlen($value) > 60 ) {
                echo('<textarea rows="10" cols="70" id="'.$field.'" name="'.$field.'">'.htmlent_utf8($value).'</textarea>'."\n");
            } else {
                echo('<input type="text" size="80" id="'.$field.'" name="'.$field.'" value="'.htmlent_utf8($value).'">'."\n");
            }
            echo("</label>\n</div>");
        }
        if ( $do_edit ) {
            echo('<input type="submit" name="doUpdate" class="btn btn-normal" value="'._m("Update").'">');
            echo('</form>'."\n");
        }
        return true;
    }

    public static function insertHandle($tablename, $fields) {
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
                if ( strpos($field, "_sha256") !== false ) {
                    $key = str_replace("_sha256", "_key", $field);
                    if ( ! isset($_POST[$key]) ) {
                        $_SESSION['success'] = "Missing POST field: ".$key;
                        return self::INSERT_FAIL;
                    }
                    $value = lti_sha256($_POST[$key]);
                } else {
                    if ( isset($_POST[$field]) ) {
                        $value = $_POST[$field];
                    } else {
                        $_SESSION['success'] = "Missing POST field: ".$field;
                        return self::INSERT_FAIL;
                    }
                }
                $parms[':'.$i] = $value;
                $values .= ":".$i;
            }
            $sql = "INSERT INTO $tablename \n( $names ) VALUES ( $values )";
            $stmt = $PDOX->queryDie($sql, $parms);
            $_SESSION['success'] = _m("Record Inserted");
            return self::INSERT_SUCCESS;
        }
        return self::INSERT_NONE;
    }

    public static function insertForm($fields, $from_location) {
        echo('<form method="post">'."\n");

        for($i=0; $i < count($fields); $i++ ) {
            $field = $fields[$i];

            // Don't allow setting of these fields
            if ( strpos($field, "_at") > 0 ) continue;
            if ( strpos($field, "_sha256") > 0 ) continue;

            echo('<div class="form-group">'."\n");
            echo('<label for="'.$field.'">'.self::fieldToTitle($field)."<br/>\n");

            if ( strpos($field, "secret") !== false ) {
                echo('<input id="'.$field.'" type="password" size="80" name="'.$field.'"');
                echo("onclick=\"if ( $(this).attr('type') == 'text' ) $(this).attr('type','password'); else $(this).attr('type','text'); return false;\">\n");
            } else {
                echo('<input type="text" size="80" id="'.$field.'" name="'.$field.'">'."\n");
            }
            echo("</label>\n</div>");
        }

        echo('<input type="submit" name="doSave" class="btn btn-normal" value="'._m("Save").'">'."\n");
        echo('<a href="'.$from_location.'" class="btn btn-default">Cancel</a>'."\n");
        echo('</form>'."\n");
    }

}
