<?php

/**
 * Simple autoLoader
 *
 * @param $class_name - String name for the class that is trying to be loaded.
 */
function autoLoader($class_name)
{
  // autoload helpers
  include_once("src/helpers/dataTypes.php");
  include_once("src/helpers/json.php");

  $file = __DIR__ . '/includes/classes/' . $class_name . '.php';

  if (file_exists($file)) {
    require_once $file;
  }
}

// add a new autoLoader by passing a callable into spl_autoload_register()
spl_autoload_register('autoLoader');
