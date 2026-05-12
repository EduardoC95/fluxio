<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Proposal;
use App\Support\DocumentNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_numbers_are_reserved_in_sequence_table(): void
    {
        $customer = Entity::query()->create([
            'number' => 1001,
            'name' => 'Cliente Teste',
            'is_customer' => true,
            'is_active' => true,
        ]);

        Proposal::query()->create([
            'number' => sprintf('PROP-%s-0003', now()->format('Y')),
            'proposal_date' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
            'entity_id' => $customer->id,
            'line_items' => [['name' => 'Item', 'quantity' => 1, 'unit_price' => 10]],
            'totals' => ['total' => 10],
            'status' => 'draft',
        ]);

        $this->assertSame(sprintf('PROP-%s-0004', now()->format('Y')), DocumentNumberGenerator::nextDocument(Proposal::class, 'PROP'));
        $this->assertSame(sprintf('PROP-%s-0005', now()->format('Y')), DocumentNumberGenerator::nextDocument(Proposal::class, 'PROP'));

        $this->assertDatabaseHas('document_number_sequences', [
            'sequence_key' => sprintf('%s:number:PROP:%s', Proposal::class, now()->format('Y')),
            'last_number' => 5,
        ]);
    }

    public function test_numeric_numbers_are_reserved_in_sequence_table(): void
    {
        Entity::query()->create([
            'number' => 1010,
            'name' => 'Cliente Teste',
            'is_customer' => true,
            'is_active' => true,
        ]);

        $this->assertSame(1011, DocumentNumberGenerator::nextNumeric(Entity::class));
        $this->assertSame(1012, DocumentNumberGenerator::nextNumeric(Entity::class));

        $this->assertSame(1012, DB::table('document_number_sequences')
            ->where('sequence_key', Entity::class.':number:numeric')
            ->value('last_number'));
    }
}
