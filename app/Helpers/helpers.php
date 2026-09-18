<?php
/**
 * Custom helper functions for things we do a lot.
 */

if (! function_exists('trimp')) {
    /**
     * Trim+
     * Trims default trim characters plus whatever additional characters are supplied.
     *
     * @param string $subject
     * @param string $characters
     * @return string
     */
    function trimp(string $subject, string $characters): string {
        /**
         * @var Closure
         * use mb_trim if it exists, regular if not
         */
        $trim =  function_exists('mb_trim') ? mb_trim(...) : trim(...);
        return $trim($trim($subject), $characters);
    }
}

if (! function_exists('emptyToNull')) {
    /**
     * Converts empty string to null.
     * Operates on either a single, scalar string or an array of strings.
     *
     * @param null|string|array $subject
     * @return array|bool|mixed|string|null
     */
    function emptyToNull(null|string|array $subject): string|array|null
    {
        $unwrap = ! is_array($subject);
        if ($unwrap) {
            $subject = [$subject];            
        }
        foreach ($subject as $k => $v) {
            $subject[$k] = empty($v) ? null : $v;
        }
        if ($unwrap) {
            $subject = reset($subject);
        }
        return $subject;
    }
}

if (! function_exists('reorderKeys')) {
    /**
     * Given an array, creates a new array with keys and order specified by $newOrder.
     * The returned array will have an element keyed to every element in $newOrder.
     * If a value for a given key is missing in the original, $fill will be used instead in the returned array.
     *
     * @param array $subject
     * @param array $newOrder
     * @return array
     */
    function reorderKeys(array $subject, array $newOrder, mixed $fill = null): array
    {
        $empire = [];
        foreach ($newOrder as $key) {
            $empire[$key] = $subject[$key] ?? $fill;
        }
        return $empire;
    }
}

if (! function_exists('callerId')) {
    function callerId(?string $returnType = 'array'): array|string|null
    {
        $returnType = in_array($returnType, ['array', 'string']) ? $returnType : 'array';

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (empty($trace[1])) {
            return null;
        }

        $caller = $trace[1];
        if ('array' === $returnType) {
            return $caller;
        }

        $class = $caller['class'] ?? '';
        $type = $caller['type'] ?? '';
        $function = $caller['function'];

        return "{$class}{$type}{$function}";
    }
}