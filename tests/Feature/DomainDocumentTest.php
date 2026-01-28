<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\Domain;
use App\Models\DomainDocument;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_document_can_be_created(): void
    {
        $partner = Partner::factory()->create();
        $user = User::factory()->create(['partner_id' => $partner->id]);
        $domain = Domain::factory()->create([
            'partner_id' => $partner->id,
            'client_id' => $user->id,
        ]);

        $document = DomainDocument::factory()->create([
            'domain_id' => $domain->id,
            'uploaded_by' => $user->id,
        ]);

        $this->assertDatabaseHas('domain_documents', [
            'id' => $document->id,
            'domain_id' => $domain->id,
            'uploaded_by' => $user->id,
        ]);
    }

    public function test_domain_document_belongs_to_domain(): void
    {
        $domain = Domain::factory()->create();
        $document = DomainDocument::factory()->create(['domain_id' => $domain->id]);

        $this->assertInstanceOf(Domain::class, $document->domain);
        $this->assertEquals($domain->id, $document->domain->id);
    }

    public function test_domain_document_belongs_to_uploader(): void
    {
        $user = User::factory()->create();
        $document = DomainDocument::factory()->create(['uploaded_by' => $user->id]);

        $this->assertInstanceOf(User::class, $document->uploader);
        $this->assertEquals($user->id, $document->uploader->id);
    }

    public function test_domain_document_can_have_verifier(): void
    {
        $verifier = User::factory()->create();
        $document = DomainDocument::factory()->verified()->create(['verified_by' => $verifier->id]);

        $this->assertInstanceOf(User::class, $document->verifier);
        $this->assertEquals($verifier->id, $document->verifier->id);
    }

    public function test_domain_has_many_documents(): void
    {
        $domain = Domain::factory()->create();
        DomainDocument::factory()->count(3)->create(['domain_id' => $domain->id]);

        $this->assertCount(3, $domain->documents);
        $this->assertInstanceOf(DomainDocument::class, $domain->documents->first());
    }

    public function test_document_type_enum_is_cast_correctly(): void
    {
        $document = DomainDocument::factory()->create([
            'document_type' => DocumentType::IdentityProof,
        ]);

        $this->assertInstanceOf(DocumentType::class, $document->document_type);
        $this->assertEquals(DocumentType::IdentityProof, $document->document_type);
    }

    public function test_is_verified_returns_false_for_unverified_document(): void
    {
        $document = DomainDocument::factory()->create([
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->assertFalse($document->isVerified());
    }

    public function test_is_verified_returns_true_for_verified_document(): void
    {
        $document = DomainDocument::factory()->verified()->create();

        $this->assertTrue($document->isVerified());
    }

    public function test_verify_method_marks_document_as_verified(): void
    {
        $verifier = User::factory()->create();
        $document = DomainDocument::factory()->create([
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $document->verify($verifier, 'Looks good');

        $document->refresh();

        $this->assertEquals($verifier->id, $document->verified_by);
        $this->assertNotNull($document->verified_at);
        $this->assertEquals('Looks good', $document->notes);
        $this->assertTrue($document->isVerified());
    }

    public function test_document_can_be_soft_deleted(): void
    {
        $document = DomainDocument::factory()->create();

        $document->delete();

        $this->assertSoftDeleted('domain_documents', ['id' => $document->id]);
    }

    public function test_soft_deleted_documents_can_be_restored(): void
    {
        $document = DomainDocument::factory()->create();
        $document->delete();

        $document->restore();

        $this->assertDatabaseHas('domain_documents', [
            'id' => $document->id,
            'deleted_at' => null,
        ]);
    }

    public function test_document_is_deleted_when_domain_is_deleted(): void
    {
        $domain = Domain::factory()->create();
        $document = DomainDocument::factory()->create(['domain_id' => $domain->id]);

        $domain->forceDelete();

        $this->assertDatabaseMissing('domain_documents', ['id' => $document->id]);
    }

    public function test_all_document_types_are_valid(): void
    {
        foreach (DocumentType::cases() as $type) {
            $document = DomainDocument::factory()->create(['document_type' => $type]);
            
            $this->assertEquals($type, $document->document_type);
        }
    }

    public function test_document_type_has_label_method(): void
    {
        $this->assertEquals('Identity Proof', DocumentType::IdentityProof->label());
        $this->assertEquals('Address Proof', DocumentType::AddressProof->label());
        $this->assertEquals('Authorization Letter', DocumentType::AuthorizationLetter->label());
        $this->assertEquals('Other', DocumentType::Other->label());
    }

    public function test_verified_at_is_cast_to_datetime(): void
    {
        $document = DomainDocument::factory()->verified()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $document->verified_at);
    }

    public function test_file_size_is_cast_to_integer(): void
    {
        $document = DomainDocument::factory()->create(['file_size' => 12345]);

        $this->assertIsInt($document->file_size);
        $this->assertEquals(12345, $document->file_size);
    }

    public function test_document_stores_original_filename(): void
    {
        $document = DomainDocument::factory()->create([
            'original_filename' => 'passport.pdf',
            'file_path' => 'documents/abc123.pdf',
        ]);

        $this->assertEquals('passport.pdf', $document->original_filename);
        $this->assertNotEquals($document->original_filename, basename($document->file_path));
    }

    public function test_multiple_documents_can_have_same_type_for_domain(): void
    {
        $domain = Domain::factory()->create();
        
        $doc1 = DomainDocument::factory()->create([
            'domain_id' => $domain->id,
            'document_type' => DocumentType::IdentityProof,
        ]);
        
        $doc2 = DomainDocument::factory()->create([
            'domain_id' => $domain->id,
            'document_type' => DocumentType::IdentityProof,
        ]);

        $this->assertEquals(2, $domain->documents()->where('document_type', DocumentType::IdentityProof)->count());
    }

    public function test_document_notes_are_optional(): void
    {
        $document = DomainDocument::factory()->create(['notes' => null]);

        $this->assertNull($document->notes);
    }

    public function test_verify_method_works_without_notes(): void
    {
        $verifier = User::factory()->create();
        $document = DomainDocument::factory()->create();

        $document->verify($verifier);

        $document->refresh();

        $this->assertEquals($verifier->id, $document->verified_by);
        $this->assertNotNull($document->verified_at);
        $this->assertNull($document->notes);
    }
}
