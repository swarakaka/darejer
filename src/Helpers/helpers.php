<?php

if (! function_exists('__darejer')) {
    /**
     * Translate a string using the Darejer package translation namespace.
     * Usage: __darejer('Save') => looks up darejer::darejer.Save
     */
    function __darejer(string $key, array $replace = [], ?string $locale = null): string
    {
        return __("darejer::darejer.{$key}", $replace, $locale);
    }
}
