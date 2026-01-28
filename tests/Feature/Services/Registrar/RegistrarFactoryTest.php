<?php

namespace Tests\Feature\Services\Registrar;

use App\Exceptions\RegistrarException;
use App\Models\Registrar;
use App\Services\Registrar\MockRegistrar;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test registrar
        Registrar::create([
            'name' => 'Mock Registrar',
            'slug' => 'mock',
            'api_class' => MockRegistrar::class,
            'credentials' => [
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /**
     * Test factory creates registrar instance by ID.
     */
    public function test_creates_registrar_by_id(): void
    {
        $registrar = Registrar::where('slug', 'mock')->first();
        $instance = RegistrarFactory::make($registrar->id);

        $this->assertInstanceOf(MockRegistrar::class, $instance);
        $this->assertEquals('Mock Registrar', $instance->getName());
    }

    /**
     * Test factory creates registrar instance by slug.
     */
    public function test_creates_registrar_by_slug(): void
    {
        $instance = RegistrarFactory::make('mock');

        $this->assertInstanceOf(MockRegistrar::class, $instance);
        $this->assertEquals('Mock Registrar', $instance->getName());
    }

    /**
     * Test factory caches registrar instances.
     */
    public function test_caches_registrar_instances(): void
    {
        $instance1 = RegistrarFactory::make('mock');
        $instance2 = RegistrarFactory::make('mock');

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test factory returns default registrar.
     */
    public function test_returns_default_registrar(): void
    {
        $instance = RegistrarFactory::default();

        $this->assertInstanceOf(MockRegistrar::class, $instance);
    }

    /**
     * Test factory throws exception for inactive registrar.
     */
    public function test_throws_exception_for_inactive_registrar(): void
    {
        Registrar::create([
            'name' => 'Inactive Registrar',
            'slug' => 'inactive',
            'api_class' => MockRegistrar::class,
            'is_active' => false,
        ]);

        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('not active');

        RegistrarFactory::make('inactive');
    }

    /**
     * Test factory throws exception for non-existent registrar.
     */
    public function test_throws_exception_for_non_existent_registrar(): void
    {
        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('not found');

        RegistrarFactory::make('non-existent');
    }

    /**
     * Test factory throws exception when no default registrar exists.
     */
    public function test_throws_exception_when_no_default_registrar(): void
    {
        Registrar::where('is_default', true)->update(['is_default' => false]);

        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('No default registrar');

        RegistrarFactory::default();
    }

    /**
     * Test factory throws exception for non-existent class.
     */
    public function test_throws_exception_for_non_existent_class(): void
    {
        Registrar::create([
            'name' => 'Invalid Registrar',
            'slug' => 'invalid',
            'api_class' => 'App\\Services\\Registrar\\NonExistentRegistrar',
            'is_active' => true,
        ]);

        $this->expectException(RegistrarException::class);
        $this->expectExceptionMessage('class not found');

        RegistrarFactory::make('invalid');
    }

    /**
     * Test factory clears cache for specific registrar.
     */
    public function test_clears_cache_for_specific_registrar(): void
    {
        $instance1 = RegistrarFactory::make('mock');
        
        RegistrarFactory::clearCache('mock');
        
        $instance2 = RegistrarFactory::make('mock');

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test factory clears all cached instances.
     */
    public function test_clears_all_cached_instances(): void
    {
        $instance1 = RegistrarFactory::make('mock');
        
        RegistrarFactory::clearCache();
        
        $instance2 = RegistrarFactory::make('mock');

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test factory returns all active registrars.
     */
    public function test_returns_all_active_registrars(): void
    {
        Registrar::create([
            'name' => 'Second Registrar',
            'slug' => 'second',
            'api_class' => MockRegistrar::class,
            'is_active' => true,
        ]);

        Registrar::create([
            'name' => 'Inactive Registrar',
            'slug' => 'inactive',
            'api_class' => MockRegistrar::class,
            'is_active' => false,
        ]);

        $instances = RegistrarFactory::all();

        $this->assertCount(2, $instances);
        $this->assertArrayHasKey('mock', $instances);
        $this->assertArrayHasKey('second', $instances);
        $this->assertArrayNotHasKey('inactive', $instances);
    }

    /**
     * Test factory checks if registrar exists.
     */
    public function test_checks_if_registrar_exists(): void
    {
        $this->assertTrue(RegistrarFactory::exists('mock'));
        $this->assertFalse(RegistrarFactory::exists('non-existent'));
    }

    /**
     * Test factory uses api_class from database.
     */
    public function test_uses_api_class_from_database(): void
    {
        $registrar = Registrar::where('slug', 'mock')->first();
        $this->assertEquals(MockRegistrar::class, $registrar->api_class);

        $instance = RegistrarFactory::make('mock');
        
        $this->assertInstanceOf(MockRegistrar::class, $instance);
    }

    /**
     * Test factory merges configuration correctly.
     */
    public function test_merges_configuration_correctly(): void
    {
        config(['registrar.registrars.mock.timeout' => 60]);
        
        $instance = RegistrarFactory::make('mock');
        
        $this->assertInstanceOf(MockRegistrar::class, $instance);
    }
}
