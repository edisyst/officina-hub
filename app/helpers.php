<?php

if (! function_exists('setting')) {
    /**
     * Legge un'impostazione dalla tabella settings con cache in-request.
     * Wrapper conveniente per Setting::get().
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return \App\Models\Setting::get($key, $default);
    }
}
