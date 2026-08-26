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