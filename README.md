# Hookable
Hook able Library like a WordPress uses

[![Build Status](https://travis-ci.org/pentagonal/Hookable.svg?branch=master)](https://travis-ci.org/pentagonal/Hookable)
[![Latest Stable Version](https://poser.pugx.org/pentagonal/hookable/v/stable)](https://packagist.org/packages/pentagonal/hookable)
[![License](https://poser.pugx.org/pentagonal/hookable/license)](https://packagist.org/packages/pentagonal/hookable)
[![Total Downloads](https://poser.pugx.org/pentagonal/hookable/downloads)](https://packagist.org/packages/pentagonal/hookable)

### Usage

```php
$hook = new \Pentagonal\Hookable\Hookable();

/**
 * Add Hook into functions example
 */
function thIsIsOnHookReturn()
{
    global $hooks;
    /**
     * .... run the code
     */
    $the_result = array('array_result'); // the returning result
    return $hook->apply('callback_name', $the_result);
}

/**
 * in here
 * Calling thIsIsOnHookReturn()
 * will be returning array
 */
var_dump(thIsIsOnHookReturn());

/**
 * add filter / action on determined callback
 */
$hook->add(
    'callback_name', // the callback
    function ($returning_old_result) {
        $new_result = print_r($returning_result, true);
        return $new_result;
    },
    10, // priority
    1 // arguments accepted
);

/**
 * in here
 * Calling thIsIsOnHookReturn()
 * will be returning string of array printed
 */
var_dump(thIsIsOnHookReturn());

```
### Install

```json
{
   "require": {
       "pentagonal/hookable": "^1"
   }
}
```
