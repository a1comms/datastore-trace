<?php

if (!function_exists('is_gae_std_legacy')) {
    function is_gae_std_legacy() {
        return (
            isset( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'Google App Engine' ) !== false
        ||
            isset( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'Development/' ) === 0
        );
    }
}

if (!function_exists('gae_project')) {
    function gae_project() {
        if ( true ) {
            return $_SERVER['GOOGLE_CLOUD_PROJECT'];
        } else {
            return false;
        }
    }
}

if ( is_gae_std_legacy() ) {
    define('GAE_LEGACY', true);
} else {
    define('GAE_LEGACY', false);
}

if ( GAE_LEGACY ) {
    $_SERVER['GOOGLE_CLOUD_PROJECT'] = explode("~", $_SERVER['APPLICATION_ID'])[1];
    $_SERVER['GAE_ENV'] = "standard";
    try {
        $current_version = explode('.', $_SERVER['CURRENT_VERSION_ID']);
        $_SERVER['GAE_VERSION'] = $current_version[0];
    } catch (Exception $e) {
        $_SERVER['GAE_VERSION'] = $_SERVER['CURRENT_VERSION_ID'];
    }
    $_SERVER['GAE_SERVICE'] = $_SERVER['CURRENT_MODULE_ID'];
    $_SERVER['GAE_INSTANCE'] = $_SERVER['INSTANCE_ID'];
}

function getHandler()
{
    if (GAE_LEGACY) {
        return new GDS\Gateway\ProtoBuf(null, null);
    } else {
        if (!empty($_GET['rest'])) {
            return new GDS\Gateway\RESTv1(gae_project(), null);
        }

        return new GDS\Gateway\GRPCv1(gae_project(), null);
    }
}