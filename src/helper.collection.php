<?php

use Intoy\HebatSupport\Env;
use Intoy\HebatSupport\Arr;
use Intoy\HebatSupport\Collection;
use Intoy\HebatSupport\HigherOrderTapProxy;

/**
 * Helper collection
 * ================
 * value
 * is_value
 * env
 * collect
 * data_get
 * data_set
 * data_unset
 * data_fill
 * data_build
 * head
 * last
 * tap
 * with
 */

if (!function_exists('value'))
{
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value, ...$args) { return $value instanceof Closure ? $value(...$args) : $value; }
}


if(!function_exists('is_value'))
{
    /**
     * Check variable has valueable
     *
     * @param  mixed  $value
     * @return bool
     */
    function is_value($val=null)
    {
        if(is_string($val))
        {
            return strlen(trim((string)$val))>0;
        }
        else {
            return isset($val);
        }
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function env($key, $default = null) { return Env::get($key, $default); }
}

if (!function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     * @return Collection
     */
    function collect($value = null)
    {
        return new Collection($value);
    }
}

if (!function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed  $target
     * @param  string|array|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (!function_exists('data_set'))
{
    /**
     * Set an item on an array or object using dot notation.
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  bool  $overwrite
     * @return mixed
     */
    function data_set(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (! Arr::accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target)) {
            if ($segments) {
                if (! Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}

if(!function_exists('data_unset')) {
    /**
     * Unset attribut any variable array or object
     *
     * @param  mixed  $target
     * @param  string|array  $prop
     * @param  bool $refill;
     */
    function data_unset(&$target, $prop, $refill=true)
    {
        if(is_array($prop))
        {
            foreach($prop as $key)
            {
                data_unset($target,$key);
            }
            if(is_array($target) && $refill)
            {
                $target=[...$target]; // re-fill
            }
        }
        if(is_array($target))
        {
            $keys=array_keys($target);
            // check if key by numeric as index
            if(is_numeric($prop) && isset($keys[0]) && is_numeric($keys[0]))
            {
                unset($target[$prop]);
            }
            elseif(($key=array_search($prop,$target))!==false)
            {
                unset($target[$key]);
            }
            elseif(isset($target[$prop]))
            {
                unset($target[$prop]);
            }
            else {
                Arr::forget($target,$prop);
            }
        }
        elseif(is_object($target) && property_exists($target,$prop))
        {
            try {
                unset($target->$prop);
            }
            catch(\Exception $e)
            {
            }
        }
    }
}


if (!function_exists('data_fill')) {
    /**
     * Fill in data where it's missing.
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @return mixed
     */
    function data_fill(&$target, $key, $value)
    {
        return data_set($target, $key, $value, false);
    }
}


if(!function_exists("data_build"))
{
    /**
     * Build data from arguments by name/value
     * @param mixed $args for arguments function
     * @return array
     */
    function data_build(...$args)
    {
        $arguments=\func_get_args();
        $count=count($arguments);      
        if($count<1) { return []; }
        $trace = debug_backtrace();
        $file_name=$trace[0]['file'];
        $file_line=$trace[0]['line'];
        $file_content=file($file_name);
        $script_snap=implode("",array_slice($file_content,$file_line-1));
        $script_snap=preg_replace('/\s+/','',$script_snap);
        $pattern='/data\_build[\w|\s|\(]+((\$[\w|\s|\,]+)+)+(\)|\s)+(?=\))/mi';
        preg_match_all($pattern, $script_snap, $match);
        $go=$match?$match[0]:[];
        $go=implode("",$go);
        preg_match_all("#\\$(\w+)#", $go, $go_match);
        $var_names=$go_match && is_array($go_match[1])?$go_match[1]:[];
        $compact=[];
        foreach($var_names as $key => $name)
        {
            if(!is_callable($arguments[$key]))
            {
                $compact[$name]=$arguments[$key];
            }
        }
        return $compact;     
    }
}


if (!function_exists('head')) {
    /**
     * Get the first element of an array. Useful for method chaining.
     *
     * @param  array  $array
     * @return mixed
     */
    function head($array)
    {
        return reset($array);
    }
}


if (!function_exists('last')) {
    /**
     * Get the last element from an array.
     *
     * @param  array  $array
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }
}


if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }

        $callback($value);

        return $value;
    }
}

if (!function_exists('with')) {
    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function with($value, callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }
}