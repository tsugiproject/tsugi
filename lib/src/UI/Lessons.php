<?php

namespace Tsugi\UI;

/**
 * Old class name for LTI tools outside this repo.
 *
 * Those tools do `new \Tsugi\UI\Lessons($CFG->lessons)` and look up a
 * resource link. The document lives on LessonsService. This subclass
 * keeps that constructor and those methods available under the old name.
 */
class Lessons extends \Tsugi\Services\Lessons\LessonsService
{
}
