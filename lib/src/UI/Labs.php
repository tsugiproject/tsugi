<?php

namespace Tsugi\UI;

use Tsugi\Controllers\Login;
use Tsugi\Grades\GradeUtil;
use Tsugi\Util\U;

/**
 * Labs catalog: LTI items in lessons JSON marked with "labs": true.
 */
class Labs {

    /**
     * @return array<int, array{module: object, item: object}>
     */
    public static function collectLtiItems($lessons_path) {
        $items = array();
        if ( ! is_readable($lessons_path) ) {
            return $items;
        }

        $lessons = json_decode(file_get_contents($lessons_path));
        if ( ! isset($lessons->modules) || ! is_array($lessons->modules) ) {
            return $items;
        }

        foreach ( $lessons->modules as $module ) {
            if ( isset($module->hidden) && $module->hidden ) {
                continue;
            }
            if ( isset($module->items) && is_array($module->items) ) {
                foreach ( $module->items as $item ) {
                    $item_obj = is_array($item) ? (object) $item : $item;
                    if ( ! isset($item_obj->type) || $item_obj->type !== 'lti' ) {
                        continue;
                    }
                    if ( empty($item_obj->labs) ) {
                        continue;
                    }
                    if ( ! isset($item_obj->resource_link_id) ) {
                        continue;
                    }
                    $items[] = array('module' => $module, 'item' => $item_obj);
                }
                continue;
            }

            if ( isset($module->lti) ) {
                $ltis = $module->lti;
                if ( ! is_array($ltis) ) {
                    $ltis = array($ltis);
                }
                foreach ( $ltis as $lti ) {
                    $lti_obj = is_array($lti) ? (object) $lti : $lti;
                    if ( empty($lti_obj->labs) ) {
                        continue;
                    }
                    if ( ! isset($lti_obj->resource_link_id) ) {
                        continue;
                    }
                    $items[] = array('module' => $module, 'item' => $lti_obj);
                }
            }
        }

        return $items;
    }

    public static function canLaunch() {
        return U::get($_SESSION, 'secret')
            && U::get($_SESSION, 'context_key')
            && U::get($_SESSION, 'user_key')
            && U::get($_SESSION, 'displayname')
            && U::get($_SESSION, 'email');
    }

    public static function printStyles() {
        Lessons::printLtiProgressStyles();
        echo('<style>
:root {
    --labs-accent: #5b21b6;
    --labs-accent-light: #ede9fe;
}
#container.tsugi-labs-home,
#container.tsugi-labs-view {
    max-width: 960px;
}
.tsugi-labs-banner {
    background: linear-gradient(135deg, var(--labs-accent) 0%, #7c3aed 100%);
    color: #fff;
    padding: 1.25rem 1.5rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}
.tsugi-labs-banner h1 {
    margin: 0 0 0.35rem 0;
    color: #fff;
    font-size: 1.75rem;
}
.tsugi-labs-banner p {
    margin: 0;
    opacity: 0.95;
}
.tsugi-labs-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}
.tsugi-labs-card {
    border: 1px solid #ddd;
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
    background: var(--labs-accent-light);
}
.tsugi-labs-card h2 {
    margin-top: 0;
    font-size: 1.15rem;
}
.tsugi-labs-view h1 { margin-top: 0; }
</style>'."\n");
    }

    public static function renderHome() {
        global $CFG;

        $home = $CFG->apphome . '/';
        $labs = $CFG->apphome . '/labs';
        $login = Login::loginUrl();
        $assignments = $CFG->apphome . '/assignments';
        $privacy = $CFG->apphome . '/privacy';

        echo('<main id="container" class="tsugi-labs-home">'."\n");
        echo('<div class="tsugi-labs-banner">'."\n");
        echo('<h1>'.htmlentities($CFG->context_title).'</h1>'."\n");
        if ( isset($CFG->servicedesc) && $CFG->servicedesc ) {
            echo('<p>'.htmlentities($CFG->servicedesc).'</p>'."\n");
        }
        echo('</div>'."\n");

        if ( isset($_SESSION['id']) ) {
            echo('<p>'.__('Welcome. This site focuses on interactive, autograded activities.').' ');
            echo(__('Use the').' <a href="'.htmlspecialchars($labs).'">'.__('Labs').'</a> ');
            echo(__('page to jump directly to LTI tools.').'</p>'."\n");
        } else {
            echo('<p>'.__('This site focuses on interactive, autograded activities.').' ');
            echo('<a href="'.htmlspecialchars($login).'">'.__('Log in').'</a> ');
            echo(__('to launch autograders and track your progress, or browse the').' ');
            echo('<a href="'.htmlspecialchars($labs).'">'.__('Labs catalog').'</a> ');
            echo(__('to see available activities.').'</p>'."\n");
        }

        echo('<div class="tsugi-labs-card-grid">'."\n");
        echo('<div class="tsugi-labs-card"><h2><a href="'.htmlspecialchars($labs).'">'.__('Labs').'</a></h2>');
        echo('<p>'.__('LTI autograders and interactive tools marked for the labs experience.').'</p></div>'."\n");
        echo('<div class="tsugi-labs-card"><h2><a href="'.htmlspecialchars($assignments).'">'.__('Assignments').'</a></h2>');
        echo('<p>'.__('Track scores on autograded lab activities.').'</p></div>'."\n");
        echo('</div>'."\n");
        echo('<p>'.__('We take your privacy seriously on this site, you can review our').' ');
        echo('<a href="'.htmlspecialchars($privacy).'">'.__('Privacy Policy').'</a> ');
        echo(__('for more details.').'</p>'."\n");
        echo('</main>'."\n");
    }

    public static function renderCatalog($lessons_path) {
        global $CFG;

        $labs_items = self::collectLtiItems($lessons_path);
        $can_launch = self::canLaunch();
        $launch_base = $CFG->apphome . '/lessons_launch/';

        $allgrades = array();
        $duedates = array();
        if ( $can_launch ) {
            foreach ( GradeUtil::loadGradesCurrentUser() as $row ) {
                $allgrades[$row['resource_link_id']] = $row['grade'];
            }
            if ( U::currentContextId() !== 0 ) {
                $duedates = GradeUtil::loadDueDatesForDisplay(U::currentContextId());
            }
        }

        echo('<main id="container" class="tsugi-labs-view">'."\n");
        echo('<h1>'.__('Interactive Labs').'</h1>'."\n");
        echo('<p>'.__('These are hands-on LTI activities marked for the labs view.').' ');
        if ( ! $can_launch ) {
            echo('<a href="'.htmlspecialchars(Login::loginUrl()).'">'.__('Log in').'</a> ');
            echo(__('to launch the tools.').' ');
        }
        echo('</p>'."\n");

        if ( count($labs_items) === 0 ) {
            echo('<p><em>'.__('No lab activities are configured yet. Add').' <code>"labs": true</code> ');
            echo(__('to LTI items in the lessons JSON.').'</em></p>'."\n");
            echo('</main>'."\n");
            return;
        }

        echo('<link rel="stylesheet" href="'.htmlspecialchars($CFG->apphome.'/css/lessons.css').'">'."\n");
        echo('<ul class="tsugi-lessons-content-list">'."\n");

        $current_module = null;
        foreach ( $labs_items as $entry ) {
            $module = $entry['module'];
            $item = $entry['item'];

            if ( $current_module !== $module->anchor ) {
                if ( $current_module !== null ) {
                    echo("</ul>\n");
                }
                $current_module = $module->anchor;
                echo('<li class="tsugi-lessons-module-title"><h2>'.htmlentities($module->title).'</h2></li>'."\n");
                if ( isset($module->description) ) {
                    echo('<li><p>'.htmlentities($module->description).'</p></li>'."\n");
                }
                echo('<ul>'."\n");
            }

            $title = isset($item->title) ? $item->title : $module->title;
            $rlid = $item->resource_link_id;
            $rl_dom_id = Lessons::domIdForResourceLink($rlid);
            echo('<li class="tsugi-lessons-module-lti" id="'.htmlspecialchars($rl_dom_id, ENT_QUOTES, 'UTF-8').'">');
            if ( $can_launch ) {
                $launch_url = $launch_base . urlencode($rlid);
                echo('<a href="'.htmlspecialchars($launch_url).'">'.htmlentities($title).'</a>');
                Lessons::echoLtiLinkProgressIndicators($rlid, $item, $allgrades, $duedates);
            } else {
                echo(htmlentities($title).' ('.__('Login Required').')');
            }
            echo("</li>\n");
        }

        if ( $current_module !== null ) {
            echo("</ul>\n");
        }
        echo("</ul>\n");
        echo('</main>'."\n");
    }
}
