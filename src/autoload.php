<?php

/**
 * Simple autoLoader
 *
 * @param $class_name - String name for the class that is trying to be loaded.
 */
function autoLoader($class_name)
{
  // autoload helpers
  foreach (glob("src/helpers/*.php") as $filename) {
    include_once $filename;
  }

  $file = __DIR__ . '/includes/classes/' . $class_name . '.php';

  if (file_exists($file)) {
    require_once $file;
  }
}

// add a new autoLoader by passing a callable into spl_autoload_register()
spl_autoload_register('autoLoader');
