<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit45172f0cba5da3539c2362e87cf28ba7
{
    public static $files = array (
        '6e3fae29631ef280660b3cdad06f25a8' => __DIR__ . '/..' . '/symfony/deprecation-contracts/function.php',
        '09f6b20656683369174dd6fa83b7e5fb' => __DIR__ . '/..' . '/symfony/polyfill-uuid/bootstrap.php',
        'b7e1c4cbafbabee94a69519a450ea263' => __DIR__ . '/..' . '/kucrut/vite-for-wp/vite-for-wp.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Webauthn\\MetadataService\\' => 25,
            'Webauthn\\' => 9,
        ),
        'S' => 
        array (
            'Symfony\\Polyfill\\Uuid\\' => 22,
            'Symfony\\Component\\Uid\\' => 22,
            'SpomkyLabs\\Pki\\' => 15,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
            'Psr\\Http\\Message\\' => 17,
            'Psr\\Http\\Client\\' => 16,
            'Psr\\EventDispatcher\\' => 20,
            'Psr\\Clock\\' => 10,
            'ParagonIE\\ConstantTime\\' => 23,
        ),
        'L' => 
        array (
            'Lcobucci\\Clock\\' => 15,
        ),
        'C' => 
        array (
            'Cose\\' => 5,
            'CBOR\\' => 5,
        ),
        'B' => 
        array (
            'Brick\\Math\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Webauthn\\MetadataService\\' => 
        array (
            0 => __DIR__ . '/..' . '/web-auth/metadata-service/src',
        ),
        'Webauthn\\' => 
        array (
            0 => __DIR__ . '/..' . '/web-auth/webauthn-lib/src',
        ),
        'Symfony\\Polyfill\\Uuid\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-uuid',
        ),
        'Symfony\\Component\\Uid\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/uid',
        ),
        'SpomkyLabs\\Pki\\' => 
        array (
            0 => __DIR__ . '/..' . '/spomky-labs/pki-framework/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/src',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
            1 => __DIR__ . '/..' . '/psr/http-factory/src',
        ),
        'Psr\\Http\\Client\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-client/src',
        ),
        'Psr\\EventDispatcher\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/event-dispatcher/src',
        ),
        'Psr\\Clock\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/clock/src',
        ),
        'ParagonIE\\ConstantTime\\' => 
        array (
            0 => __DIR__ . '/..' . '/paragonie/constant_time_encoding/src',
        ),
        'Lcobucci\\Clock\\' => 
        array (
            0 => __DIR__ . '/..' . '/lcobucci/clock/src',
        ),
        'Cose\\' => 
        array (
            0 => __DIR__ . '/..' . '/web-auth/cose-lib/src',
        ),
        'CBOR\\' => 
        array (
            0 => __DIR__ . '/..' . '/spomky-labs/cbor-php/src',
        ),
        'Brick\\Math\\' => 
        array (
            0 => __DIR__ . '/..' . '/brick/math/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit45172f0cba5da3539c2362e87cf28ba7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit45172f0cba5da3539c2362e87cf28ba7::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit45172f0cba5da3539c2362e87cf28ba7::$classMap;

        }, null, ClassLoader::class);
    }
}
