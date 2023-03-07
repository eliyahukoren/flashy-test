

<?php
/**
 * Converts objects to array
 *
 * @param mixed $obj object(s)
 *
 * @return array|mixed
 */
function objectToArray($obj)
{
  // Just return as is if not Array AND not Object
  if (!is_array($obj) && !is_object($obj))
    return $obj;

  $arr = (array) $obj;

  foreach ($arr as $key => $value) {
    $arr[$key] = objectToArray($value);
  }

  return $arr;
}
