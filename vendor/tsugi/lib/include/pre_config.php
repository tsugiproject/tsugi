<?php

// Setup I18N before autoload to prevent Laravel helpers from overriding translation function __()
// These two lines must be before autoload.php
require_once (__DIR__."/../src/Core/I18N.php");
require_once (__DIR__."/setup_i18n.php");
