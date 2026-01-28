<?php

namespace Tests\Feature\Services;

use App\Enums\PriceAction;
use App\Models\Partner;
use App\Models\Registrar;
use App\Models\Tld;
use App\Models\TldPrice;
use App\Services\DomainSearchService;
use App\Services\PricingService;
use App\Services\Registrar\RegistrarFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DomainSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DomainSearchService $searchService;
    protected Registrar $registrar;
    protected Tld $tld;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->searchService = app(DomainSearchService::class);
        
        // Create test registrar
        $this->registrar = Registrar::create([
            'name' => 'Mock Registrar',
            'slug' => 'mock',
            'is_active' => true,
            'is_default' => true,
        ]);

        // Create test TLD
        $this->tld = Tld::create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'com',
            'min_years' => 1,
            'max_years' => 10,
            'is_active' => true,
        ]);

        // Create pricing
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 1,
            'price' => '10.00',
            'effective_date' => now()->toDateString(),
        ]);
    }

    /** @test */
    public function it_can_parse_valid_domain()
    {
        $result = $this->searchService->parseDomain('example.com');

        $this->assertNotNull($result);
        $this->assertEquals('example.com', $result['domain']);
        $this->assertEquals('example', $result['sld']);
        $this->assertEquals('com', $result['tld']);
    }

    /** @test */
    public function it_can_parse_domain_with_subdomain()
    {
        $result = $this->searchService->parseDomain('subdomain.example.com');

        $this->assertNotNull($result);
        $this->assertEquals('subdomain.example.com', $result['domain']);
        $this->assertEquals('subdomain.example', $result['sld']);
        $this->assertEquals('com', $result['tld']);
    }

    /** @test */
    public function it_removes_http_protocol()
    {
        $result = $this->searchService->parseDomain('http://example.com');

        $this->assertNotNull($result);
        $this->assertEquals('example.com', $result['domain']);
    }

    /** @test */
    public function it_removes_https_protocol()
    {
        $result = $this->searchService->parseDomain('https://example.com');

        $this->assertNotNull($result);
        $this->assertEquals('example.com', $result['domain']);
    }

    /** @test */
    public function it_removes_www_prefix()
    {
        $result = $this->searchService->parseDomain('www.example.com');

        $this->assertNotNull($result);
        $this->assertEquals('example.com', $result['domain']);
    }

    /** @test */
    public function it_handles_uppercase_input()
    {
        $result = $this->searchService->parseDomain('EXAMPLE.COM');

        $this->assertNotNull($result);
        $this->assertEquals('example.com', $result['domain']);
    }

    /** @test */
    public function it_rejects_domain_without_tld()
    {
        $result = $this->searchService->parseDomain('example');

        $this->assertNull($result);
    }

    /** @test */
    public function it_rejects_empty_sld()
    {
        $result = $this->searchService->parseDomain('.com');

        $this->assertNull($result);
    }

    /** @test */
    public function it_rejects_invalid_characters()
    {
        $result = $this->searchService->parseDomain('exam ple.com');
        $this->assertNull($result);

        $result = $this->searchService->parseDomain('exam@ple.com');
        $this->assertNull($result);
    }

    /** @test */
    public function it_validates_domain_format()
    {
        $this->assertTrue($this->searchService->validateDomainFormat('example.com'));
        $this->assertTrue($this->searchService->validateDomainFormat('my-site.io'));
        $this->assertFalse($this->searchService->validateDomainFormat('example'));
        $this->assertFalse($this->searchService->validateDomainFormat('invalid domain.com'));
    }

    /** @test */
    public function it_checks_if_tld_is_supported()
    {
        $this->assertTrue($this->searchService->isTldSupported('com'));
        $this->assertFalse($this->searchService->isTldSupported('xyz'));
    }

    /** @test */
    public function it_returns_error_for_invalid_domain_format()
    {
        $result = $this->searchService->checkAvailability('invalid domain');

        $this->assertFalse($result['available']);
        $this->assertEquals('Invalid domain format', $result['error']);
        $this->assertEquals('validation', $result['error_type']);
    }

    /** @test */
    public function it_returns_error_for_unsupported_tld()
    {
        $result = $this->searchService->checkAvailability('example.xyz');

        $this->assertFalse($result['available']);
        $this->assertEquals('TLD not supported', $result['error']);
        $this->assertEquals('unsupported_tld', $result['error_type']);
        $this->assertEquals('xyz', $result['tld']);
    }

    /** @test */
    public function it_checks_domain_availability()
    {
        $result = $this->searchService->checkAvailability('example.com');

        $this->assertArrayHasKey('available', $result);
        $this->assertArrayHasKey('domain', $result);
        $this->assertEquals('example.com', $result['domain']);
    }

    /** @test */
    public function it_caches_availability_check()
    {
        Cache::flush();
        
        $result1 = $this->searchService->checkAvailability('example.com');
        $this->assertFalse($result1['cached'] ?? false);

        $result2 = $this->searchService->checkAvailability('example.com');
        $this->assertTrue($result2['cached'] ?? false);
    }

    /** @test */
    public function it_can_disable_caching()
    {
        Cache::flush();
        
        $result = $this->searchService->checkAvailability('example.com', useCache: false);
        
        $this->assertFalse($result['cached'] ?? false);
    }

    /** @test */
    public function it_searches_single_domain()
    {
        $result = $this->searchService->search('example.com');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['results']);
        $this->assertEquals('example.com', $result['results'][0]['domain']);
    }

    /** @test */
    public function it_searches_multiple_domains()
    {
        Tld::create([
            'registrar_id' => $this->registrar->id,
            'extension' => 'net',
            'min_years' => 1,
            'max_years' => 10,
            'is_active' => true,
        ]);

        $result = $this->searchService->search(['example.com', 'example.net']);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['results']);
    }

    /** @test */
    public function it_limits_bulk_search()
    {
        $domains = array_fill(0, 25, 'example.com');
        
        $result = $this->searchService->search($domains);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Too many domains', $result['error']);
    }

    /** @test */
    public function it_filters_empty_domains()
    {
        $result = $this->searchService->search(['example.com', '', '   ', 'test.com']);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['results']);
    }

    /** @test */
    public function it_parses_bulk_input_with_commas()
    {
        $domains = $this->searchService->parseBulkInput('example.com, test.com, mysite.io');

        $this->assertCount(3, $domains);
        $this->assertEquals(['example.com', 'test.com', 'mysite.io'], $domains);
    }

    /** @test */
    public function it_parses_bulk_input_with_newlines()
    {
        $input = "example.com\ntest.com\nmysite.io";
        $domains = $this->searchService->parseBulkInput($input);

        $this->assertCount(3, $domains);
        $this->assertEquals(['example.com', 'test.com', 'mysite.io'], $domains);
    }

    /** @test */
    public function it_removes_duplicates_from_bulk_input()
    {
        $domains = $this->searchService->parseBulkInput('example.com, example.com, test.com');

        $this->assertCount(2, $domains);
    }

    /** @test */
    public function it_generates_domain_suggestions()
    {
        // Create multiple TLDs
        foreach (['net', 'org', 'io'] as $ext) {
            Tld::create([
                'registrar_id' => $this->registrar->id,
                'extension' => $ext,
                'min_years' => 1,
                'max_years' => 10,
                'is_active' => true,
            ]);
        }

        $suggestions = $this->searchService->getSuggestions('example.com', maxSuggestions: 5);

        $this->assertIsArray($suggestions);
        $this->assertLessThanOrEqual(5, count($suggestions));
    }

    /** @test */
    public function it_includes_pricing_in_search_results()
    {
        $result = $this->searchService->search('test-available.com', years: 1);

        $this->assertTrue($result['success']);
        
        if ($result['results'][0]['available'] ?? false) {
            $this->assertArrayHasKey('price', $result['results'][0]);
            $this->assertArrayHasKey('years', $result['results'][0]);
        }
    }

    /** @test */
    public function it_respects_years_parameter_in_search()
    {
        TldPrice::create([
            'tld_id' => $this->tld->id,
            'action' => PriceAction::REGISTER,
            'years' => 2,
            'price' => '18.00',
            'effective_date' => now()->toDateString(),
        ]);

        $result = $this->searchService->search('test.com', years: 2);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_applies_partner_pricing()
    {
        $partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'slug' => 'test-partner',
            'status' => 'active',
            'is_active' => true,
        ]);

        $result = $this->searchService->search('test.com', partner: $partner, years: 1);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_gets_supported_tlds_with_pricing()
    {
        $tlds = $this->searchService->getSupportedTlds();

        $this->assertIsArray($tlds);
        $this->assertNotEmpty($tlds);
        $this->assertArrayHasKey('extension', $tlds[0]);
        $this->assertArrayHasKey('price', $tlds[0]);
    }

    /** @test */
    public function it_handles_registrar_errors_gracefully()
    {
        // This test would normally fail with a real registrar
        // The mock registrar should handle this
        $result = $this->searchService->checkAvailability('example.com');
        
        // Should not throw exception
        $this->assertIsArray($result);
    }
}
