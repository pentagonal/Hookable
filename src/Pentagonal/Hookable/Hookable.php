<?php
/**
 * @author awan <nawa@yahoo.com>
 * @license GPL-3.0+
 * @version 1.0
 */
namespace Pentagonal\Hookable;

/**
 * Class Hookable
 * @license  Follow WordPress GPL-3.0+
 * @see  {@link: https://wordpress.org/license} for more related WordPress License
 * @package Pentagonal\Hookable
 */
class Hookable
{
    /**
     * Merged Hooks Records
     * @var array
     */
    protected $merged = array();

    /**
     * Current Hooks Record
     * @var array
     */
    protected $current = array();

    /**
     * Actions Records
     * @var array
     */
    protected $actions = array();

    /**
     * Filter Records
     * @var array
     */
    protected $filters = array();

    /**
     * PHP5 Constructor
     */
    public function __construct()
    {
    }

    /**
     * Create Unique ID if function is not string
     *
     * @param string   $hookName determine the hook name
     * @param callable $function function to call
     * @param integer  $priority the priority of hooks
     *
     * @access private
     * @return string|bool
     */
    private function uniqueId($hookName, $function, $priority)
    {
        static $count = 0;
        if (is_string($function)) {
            return $function;
        }
        if (is_object($function)) {
            // Closures are currently implemented as objects
            $function = array($function, '');
        } elseif (!is_array($function)) {
            $function = array($function);
        }

        if (is_object($function[0])) {
            // Object Class Calling
            if (function_exists('spl_object_hash')) {
                return spl_object_hash($function[0]) . $function[1];
            } else {
                $object_id = get_class($function[0]).$function[1];
                if (!isset($function[0]->id)) {
                    if (false === $priority) {
                        return false;
                    }
                    $object_id .= isset($this->filters[$hookName][$priority])
                        ? count((array) $this->filters[$hookName][$priority])
                        : $count;
                    $function[0]->id = $count;
                    $count++;
                } else {
                    $object_id .= $function[0]->id;
                }
                return $object_id;
            }
        } elseif (is_string($function[0])) {
            // callas static
            return $function[0] . '::' . $function[1];
        }
        // unexpected result
        return null;
    }

    /**
     * Call the 'all' hook, which will process the functions hooked into it.
     *
     * The 'all' hook passes all of the arguments or parameters that were used for
     * the hook, which this function was called for.
     *
     * Inherited docs from WordPress :
     * -------------------------------
     * This function is used internally for apply_filters(), do_action(), and
     * do_action_ref_array() and is not meant to be used from outside those
     * functions. This function does not check for the existence of the all hook, so
     * it will fail unless the all hook exists prior to this function call.
     *
     * @access private
     * @param array $args The collected parameters from the hook that was called.
     */
    private function callAll($args)
    {
        if (isset($this->filters['all'])) {
            reset($this->filters['all']);
            do {
                foreach ((array) current($this->filters['all']) as $fn_arr) {
                    if (!is_null($fn_arr['function'])) {
                        call_user_func_array($fn_arr['function'], $args);
                    }
                }
            } while (next($this->filters['all']) !== false);
        }
    }

    /**
     * Sanitize keyname that must be as string and not empty
     *
     * @param string $key
     *
     * @return bool|string
     */
    public function sanitize($key)
    {
        if (is_string($key) && trim($key)) {
            return trim($key);
        }
        return false;
    }

    /**
     * Add Hooks Function it just like a WordPress add_action() / add_filter() hooks
     *
     * @param string    $hookName            Hook Name
     * @param Callable  $callable            Callable
     * @param integer   $priority            priority
     * @param integer   $accepted_args       num count of accepted args / parameter
     * @param boolean   $append              true if want to create new / append if not exists
     *
     * @return boolean
     * @throws \Exception
     */
    public function add($hookName, $callable, $priority = 10, $accepted_args = 1, $append = true)
    {
        $hookName = $this->sanitize($hookName);
        if (!$hookName) {
            throw new \Exception("Invalid Hook Name Specified", E_USER_ERROR);
        }
        // check append and has callable
        if ($this->has($hookName, $callable) && ! $append) {
            return false;
        }

        $id = $this->uniqueId($hookName, $callable, $priority);
        if ($id === null) {
            throw new \Exception(
                sprintf("Invalid callable specified on hook name %s", $hookName),
                E_USER_ERROR
            );
        }
        $hook_list[$hookName][$priority][$id] = array(
            'function' => $callable,
            'accepted_args' => $accepted_args
        );
        $this->filters = array_merge($this->filters, $hook_list);
        unset($this->merged[$hookName]);
        return true;
    }

    /**
     * Appending Hooks Function
     *
     * @param  string    $hookName            Hook Name
     * @param  Callable  $callable            Callable
     * @param  integer   $priority            priority
     * @param  integer   $accepted_args       num count of accepted args / parameter
     * @param  boolean   $create              true if want to create new if not exists
     *
     * @return boolean
     */
    public function append($hookName, $callable, $priority = 10, $accepted_args = 1, $create = true)
    {
        if ($create || ! $this->has($hookName, $callable)) {
            return $this->add($hookName, $callable, $priority, $accepted_args, true);
        }
        return false;
    }

    /**
     * Check if hook name exists
     *
     * @param  string      $hookName          Hook name
     * @param  string|mixed $function_to_check Specially Functions on Hook
     *
     * @return boolean|int
     */
    public function exists($hookName, $function_to_check = false)
    {
        $hookName = $this->sanitize($hookName);
        if (!$hookName || !isset($this->filters[$hookName])) {
            return false;
        }
        // Don't reset the internal array pointer
        $has    = !empty($this->filters[$hookName]);
        // Make sure at least one priority has a filter callback
        if ($has) {
            $exists = false;
            foreach ($this->filters[$hookName] as $callbacks) {
                if (! empty($callbacks)) {
                    $exists = true;
                    break;
                }
            }

            if (! $exists) {
                $has = false;
            }
        }

        // recheck
        if (false === $function_to_check || false === $has) {
            return $has;
        }

        if (! $id = $this->uniqueId($hookName, $function_to_check, false)) {
            return false;
        }

        foreach (array_keys($this->filters[$hookName]) as $priority) {
            if (isset($this->filters[$hookName][$priority][$id])) {
                return $priority;
            }
        }

        return false;
    }

    /**
     * Check if hook name exists
     *
     * @param  string       $hookName              Hook name
     * @param  string|mixed $function_to_check     Specially Functions on Hook
     *
     * @return boolean
     */
    public function has($hookName, $function_to_check = false)
    {
        return $this->exists($hookName, $function_to_check) !== false;
    }

    /**
     * Applying Hooks for replaceable and returning as $value param
     *
     * @param  string $hookName Hook Name replaceable
     * @param  mixed $value     returning value
     *
     * @return mixed
     */
    public function apply($hookName, $value)
    {
        $hookName = $this->sanitize($hookName);
        if (!$hookName) {
            return $value;
        }

        $args = array();
        // Do 'all' actions first.
        if (isset($this->filters['all'])) {
            $this->current[] = $hookName;
            $args = func_get_args();
            $this->callAll($args);
        }

        if (! isset($this->filters[$hookName])) {
            if (isset($this->filters['all'])) {
                array_pop($this->current);
            }
            return $value;
        }

        if (! isset($this->filters['all'])) {
            $this->current[] = $hookName;
        }

        // Sort.
        if (!isset($this->merged[$hookName])) {
            ksort($this->filters[$hookName]);
            $this->merged[$hookName] = true;
        }

        reset($this->filters[$hookName]);
        if (empty($args)) {
            $args = func_get_args();
        }
        do {
            foreach ((array) current($this->filters[$hookName]) as $fn_array) {
                if (!is_null($fn_array['function'])) {
                    $args[1] = $value;
                    $value = call_user_func_array(
                        $fn_array['function'],
                        array_slice($args, 1, (int) $fn_array['accepted_args'])
                    );
                }
            }
        } while (next($this->filters[$hookName]) !== false);

        array_pop($this->current);
        return $value;
    }

    /**
     * Call hook from existing declared hook record
     *
     * @param  string $hookName Hook Name
     * @param  string $arg      the arguments for next parameter
     *
     * @return boolean
     */
    public function call($hookName, $arg = '')
    {
        $hookName = $this->sanitize($hookName);
        if (!$hookName) {
            return false;
        }
        if (! isset($this->actions[$hookName])) {
            $this->actions[$hookName] = 1;
        } else {
            $this->actions[$hookName]++;
        }

        // Do 'all' actions first
        if (isset($this->filters['all'])) {
            $this->current[] = $hookName;
            $all_args = func_get_args();
            $this->callAll($all_args);
        }

        if (!isset($this->filters[$hookName])) {
            if (isset($this->filters['all'])) {
                array_pop($this->current);
            }
            return null;
        }

        if (!isset($this->filters['all'])) {
            $this->current[] = $hookName;
        }

        $args = array();
        if (is_array($arg) && 1 == count($arg) && isset($arg[0]) && is_object($arg[0])) {
            // array(&$this)
            $args[] =& $arg[0];
        } else {
            $args[] = $arg;
        }

        for ($a = 2, $num = func_num_args(); $a < $num; $a++) {
            $args[] = func_get_arg($a);
        }
        // Sort
        if (!isset($this->merged[$hookName])) {
            ksort($this->filters[$hookName]);
            $this->merged[$hookName] = true;
        }
        reset($this->filters[$hookName]);
        do {
            foreach ((array) current($this->filters[$hookName]) as $the_) {
                if (!is_null($the_['function'])) {
                    call_user_func_array(
                        $the_['function'],
                        array_slice($args, 0, (int) $the_['accepted_args'])
                    );
                }
            }
        } while (next($this->filters[$hookName]) !== false);
        array_pop($this->current);

        return true;
    }

    /**
     * Replace Hooks Function
     *
     * @param  string    $hookName            Hook Name
     * @param  string    $function_to_replace Function to replace
     * @param  Callable  $callable            Callable
     * @param  integer   $priority            priority
     * @param  integer   $accepted_args       num count of accepted args / parameter
     * @param  boolean   $create              true if want to create new if not exists
     *
     * @return boolean
     * @throws \Exception
     */
    public function replace(
        $hookName,
        $function_to_replace,
        $callable,
        $priority = 10,
        $accepted_args = 1,
        $create = true
    ) {
        $hookName = $this->sanitize($hookName);
        if (!$hookName) {
            throw new \Exception("Invalid Hook Name Specified", E_ERROR);
        }
        if ($this->has($hookName)) {
            $this->remove($hookName, $function_to_replace);
            return $this->add($hookName, $callable, $priority, $accepted_args, true);
        }
        if ($create) {
            return $this->add($hookName, $callable, $priority, $accepted_args, true);
        }
        return false;
    }

    /**
     * Removing Hook (remove single hook)
     *
     * @param  string  $hookName           Hook Name
     * @param  string  $function_to_remove functions that to remove from determine $hookName
     * @param  integer $priority           priority
     *
     * @return boolean
     */
    public function remove($hookName, $function_to_remove, $priority = 10)
    {
        $hookName = $this->sanitize($hookName);
        if (!$hookName) {
            return false;
        }
        $function_to_remove = $this->uniqueId($hookName, $function_to_remove, $priority);
        $r = isset($this->filters[$hookName][$priority][$function_to_remove]);
        if (true === $r) {
            unset($this->filters[$hookName][$priority][$function_to_remove]);
            if (empty($this->filters[$hookName][$priority])) {
                unset($this->filters[$hookName][$priority]);
            }
            if (empty($this->filters[$hookName])) {
                $this->filters[$hookName] = array();
            }
            unset($this->merged[$hookName]);
        }

        return $r;
    }

    /**
     * Remove all of the hooks from a filter.
     *
     * @param string   $hookName    The filter to remove hooks from.
     * @param int|bool $priority    Optional. The priority number to remove. Default false.
     *
     * @return boolean
     */
    public function removeAll($hookName, $priority = false)
    {
        if (isset($this->filters[$hookName])) {
            if (false === $priority) {
                $this->filters[$hookName] = array();
            } elseif (isset($this->filters[$hookName][$priority])) {
                $this->filters[$hookName][$priority] = array();
            }
        }
        unset($this->merged[$hookName]);
        return true;
    }

    /**
     * Current position
     *
     * @return string functions
     */
    public function current()
    {
        return end($this->current);
    }

    /**
     * Count all existences Hook
     *
     * @param string $hookName Hook name
     *
     * @return integer          Hooks Count
     */
    public function count($hookName)
    {
        $hookName = $this->sanitize($hookName);
        if (!$hookName || !isset($this->filters[$hookName])) {
            return false;
        }
        return count((array) $this->filters[$hookName]);
    }

    /**
     * Check if hook has doing
     *
     * @param string $hookName Hook name
     *
     * @return boolean           true if has doing
     */
    public function isDo($hookName = null)
    {
        if (null === $hookName) {
            return ! empty($this->current);
        }

        $hookName = $this->sanitize($hookName);
        return $hookName && in_array($hookName, $this->current);
    }

    /**
     * Check if action hook as execute
     *
     * @param string $hookName Hook Name
     *
     * @return integer Count of hook action if has did action
     */
    public function isCalled($hookName)
    {
        $hookName = $this->sanitize($hookName);
        if (!$hookName || ! isset($this->actions[$hookName])) {
            return 0;
        }

        return $this->actions[$hookName];
    }
}
