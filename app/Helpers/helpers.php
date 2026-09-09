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