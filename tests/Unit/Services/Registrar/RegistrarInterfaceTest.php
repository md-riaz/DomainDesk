<?php

namespace Tests\Unit\Services\Registrar;

use App\Contracts\RegistrarInterface;
use App\Exceptions\RegistrarException;
use Tests\TestCase;

class RegistrarInterfaceTest extends TestCase
{
    /**
     * Test that RegistrarInterface defines all required methods.
     */
    public function test_interface_defines_required_methods(): void
    {
        $reflection = new \ReflectionClass(RegistrarInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($m) => $m->getName(), $methods);

        $requiredMethods = [
            'checkAvailability',
            'register',
            'renew',
            'transfer',
            'updateNameservers',
            'getContacts',
            'updateContacts',
            'getDnsRecords',
            'updateDnsRecords',
            'getInfo',
            'lock',
            'unlock',
            'getName',
            'testConnection',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertContains(
                $method,
                $methodNames,
                "RegistrarInterface must define {$method} method"
            );
        }
    }

    /**
     * Test that checkAvailability returns bool.
     */
    public function test_check_availability_return_type(): void
    {
        $reflection = new \ReflectionClass(RegistrarInterface::class);
        $method = $reflection->getMethod('checkAvailability');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    /**
     * Test that register returns array.
     */
    public function test_register_return_type(): void
    {
        $reflection = new \ReflectionClass(RegistrarInterface::class);
        $method = $reflection->getMethod('register');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * Test that renew returns array.
     */
    public function test_renew_return_type(): void
    {
        $reflection = new \ReflectionClass(RegistrarInterface::class);
        $method = $reflection->getMethod('renew');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * Test that transfer returns array.
     */
    public function test_transfer_return_type(): void
    {
        $reflection = new \ReflectionClass(RegistrarInterface::class);
        $method = $reflection->getMethod('transfer');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * Test that lock returns bool.
     */
    public function test_lock_return_type(): void
    {
        $reflection = new \ReflectionClass(RegistrarInterface::class);
        $method = $reflection->getMethod('lock');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    /**
     * Test that unlock returns bool.
     */
    public function test_unlock_return_type(): void
    {
        $reflection = new \ReflectionClass(RegistrarInterface::class);
        $method = $reflection->getMethod('unlock');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    /**
     * Test that getName returns string.
     */
    public function test_get_name_return_type(): void
    {
        $reflection = new \ReflectionClass(RegistrarInterface::class);
        $method = $reflection->getMethod('getName');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    /**
     * Test that testConnection returns bool.
     */
    public function test_test_connection_return_type(): void
    {
        $reflection = new \ReflectionClass(RegistrarInterface::class);
        $method = $reflection->getMethod('testConnection');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }
}
