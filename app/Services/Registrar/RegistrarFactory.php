<?php

namespace App\Services\Registrar;

use App\Contracts\RegistrarInterface;
use App\Exceptions\RegistrarException;
use App\Models\Registrar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RegistrarFactory
{
    /**
     * Cache of registrar instances.
     */
    protected static array $instances = [];

    /**
     * Default registrar namespace.
     */
    protected static string $namespace = 'App\\Services\\Registrar\\';

    /**
     * Create or retrieve a registrar instance.
     *
     * @param int|string $registrar Registrar ID or slug
     * @return RegistrarInterface
     * @throws RegistrarException
     */
    public static function make(int|string $registrar): RegistrarInterface
    {
        // Get registrar model
        $registrarModel = static::resolveRegistrarModel($registrar);

        // Check if already instantiated
        $cacheKey = 'registrar_' . $registrarModel->id;
        
        if (isset(static::$instances[$cacheKey])) {
            return static::$instances[$cacheKey];
        }

        // Create new instance
        $instance = static::createInstance($registrarModel);

        // Cache the instance
        static::$instances[$cacheKey] = $instance;

        return $instance;
    }

    /**
     * Create a registrar instance from model.
     *
     * @param Registrar $registrar Registrar model
     * @return RegistrarInterface
     * @throws RegistrarException
     */
    protected static function createInstance(Registrar $registrar): RegistrarInterface
    {
        // Validate registrar is active
        if (!$registrar->is_active) {
            throw new RegistrarException(
                message: "Registrar is not active: {$registrar->name}",
                registrarName: $registrar->name,
                errorDetails: [
                    'type' => 'registrar_inactive',
                    'registrar_id' => $registrar->id,
                ]
            );
        }

        // Determine class name
        $className = static::resolveClassName($registrar);

        // Validate class exists
        if (!class_exists($className)) {
            throw new RegistrarException(
                message: "Registrar class not found: {$className}",
                registrarName: $registrar->name,
                errorDetails: [
                    'type' => 'class_not_found',
                    'class' => $className,
                ]
            );
        }

        // Validate implements interface
        if (!in_array(RegistrarInterface::class, class_implements($className) ?: [])) {
            throw new RegistrarException(
                message: "Registrar class must implement RegistrarInterface: {$className}",
                registrarName: $registrar->name,
                errorDetails: [
                    'type' => 'invalid_interface',
                    'class' => $className,
                ]
            );
        }

        // Get configuration
        $config = static::getConfig($registrar);
        $credentials = $registrar->credentials ?? [];

        try {
            // Instantiate the registrar
            $instance = new $className($config, $credentials);

            Log::info("Registrar instance created", [
                'registrar' => $registrar->name,
                'class' => $className,
            ]);

            return $instance;
        } catch (\Throwable $e) {
            throw new RegistrarException(
                message: "Failed to instantiate registrar: {$e->getMessage()}",
                registrarName: $registrar->name,
                errorDetails: [
                    'type' => 'instantiation_error',
                    'exception' => get_class($e),
                ],
                previous: $e
            );
        }
    }

    /**
     * Resolve registrar model from ID or slug.
     *
     * @param int|string $registrar Registrar ID or slug
     * @return Registrar
     * @throws RegistrarException
     */
    protected static function resolveRegistrarModel(int|string $registrar): Registrar
    {
        $query = is_numeric($registrar) 
            ? Registrar::where('id', $registrar)
            : Registrar::where('slug', $registrar);

        $model = $query->first();

        if (!$model) {
            throw new RegistrarException(
                message: "Registrar not found: {$registrar}",
                registrarName: 'Unknown',
                errorDetails: [
                    'type' => 'registrar_not_found',
                    'identifier' => $registrar,
                ]
            );
        }

        return $model;
    }

    /**
     * Resolve the class name for a registrar.
     *
     * @param Registrar $registrar Registrar model
     * @return string
     */
    protected static function resolveClassName(Registrar $registrar): string
    {
        // Use api_class if specified in database
        if (!empty($registrar->api_class)) {
            return $registrar->api_class;
        }

        // Generate class name from slug
        // e.g., "resellerclub" -> "ResellerClubRegistrar"
        $className = str($registrar->slug)
            ->studly()
            ->append('Registrar')
            ->toString();

        return static::$namespace . $className;
    }

    /**
     * Get configuration for a registrar.
     *
     * @param Registrar $registrar Registrar model
     * @return array
     */
    protected static function getConfig(Registrar $registrar): array
    {
        $defaultConfig = config('registrar.defaults', []);
        $registrarConfig = config("registrar.registrars.{$registrar->slug}", []);

        return array_merge($defaultConfig, $registrarConfig, [
            'name' => $registrar->name,
            'slug' => $registrar->slug,
            'registrar_id' => $registrar->id,
        ]);
    }

    /**
     * Get the default registrar instance.
     *
     * @return RegistrarInterface
     * @throws RegistrarException
     */
    public static function default(): RegistrarInterface
    {
        $defaultRegistrar = Registrar::where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (!$defaultRegistrar) {
            throw new RegistrarException(
                message: 'No default registrar configured',
                registrarName: 'System',
                errorDetails: ['type' => 'no_default_registrar']
            );
        }

        return static::make($defaultRegistrar->id);
    }

    /**
     * Clear cached registrar instance.
     *
     * @param int|string|null $registrar Registrar ID, slug, or null for all
     */
    public static function clearCache(int|string|null $registrar = null): void
    {
        if ($registrar === null) {
            static::$instances = [];
            Log::info('All registrar instances cleared from cache');
            return;
        }

        try {
            $model = static::resolveRegistrarModel($registrar);
            $cacheKey = 'registrar_' . $model->id;
            unset(static::$instances[$cacheKey]);
            
            Log::info("Registrar instance cleared from cache", [
                'registrar' => $model->name,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to clear registrar cache", [
                'registrar' => $registrar,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all active registrars.
     *
     * @return array Array of registrar instances
     */
    public static function all(): array
    {
        $registrars = Registrar::where('is_active', true)->get();
        $instances = [];

        foreach ($registrars as $registrar) {
            try {
                $instances[$registrar->slug] = static::make($registrar->id);
            } catch (RegistrarException $e) {
                Log::warning("Failed to load registrar", [
                    'registrar' => $registrar->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $instances;
    }

    /**
     * Check if a registrar class exists.
     *
     * @param string $slug Registrar slug
     * @return bool
     */
    public static function exists(string $slug): bool
    {
        try {
            $registrar = static::resolveRegistrarModel($slug);
            $className = static::resolveClassName($registrar);
            
            return class_exists($className) && 
                   in_array(RegistrarInterface::class, class_implements($className) ?: []);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Set custom namespace for registrar classes.
     *
     * @param string $namespace Namespace
     */
    public static function setNamespace(string $namespace): void
    {
        static::$namespace = rtrim($namespace, '\\') . '\\';
    }
}
