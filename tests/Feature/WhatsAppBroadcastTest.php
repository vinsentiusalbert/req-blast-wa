<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WhatsAppBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_personalization_preview_storage_and_edit_preservation(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $template->update(['header_type' => 'TEXT', 'header_text' => 'Hai {{nama}}', 'body' => 'Produk {{produk}} untuk {{nama}}']);
        $csv = fn () => UploadedFile::fake()->createWithContent('penerima.csv', "\xEF\xBB\xBFmsisdn,nama,produk\n6281234567890,Budi,\"Sepatu, Merah\"\n6281234567891,Siti,Tas");
        $this->actingAs($user)->postJson(route('user.whatsapp.broadcasts.preview-csv'), [
            'whatsapp_template_id' => $template->id, 'recipients_csv' => $csv(),
        ])->assertOk()->assertJsonPath('first.nama', 'Budi')->assertJsonPath('count', 2);
        $this->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'CSV promo', 'whatsapp_template_id' => $template->id, 'recipients_csv' => $csv(),
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $this->assertSame('Tas', $broadcast->recipient_records[1]['produk']);
        $this->assertSame('6281234567890', $broadcast->recipients[0]);
        $this->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()
            ->assertSee('Hai Budi')->assertSee('Produk Sepatu, Merah untuk Budi')->assertDontSee('Hai {{nama}}');
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Updated', 'whatsapp_template_id' => $template->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame($broadcast->recipient_records, $broadcast->fresh()->recipient_records);
    }

    public function test_invalid_csv_is_rejected_without_saving(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $template->update(['body' => 'Hai {{nama}}']);
        $this->actingAs($user);
        foreach ([
            "msisdn,nama\n081234567890,Budi",
            "msisdn,nama\n+6281234567890,Budi",
            "msisdn,nama\n14155552671,Budi",
            "nama,msisdn\nBudi,6281234567890",
            "msisdn,nama,nama\n6281234567890,Budi,Budi",
            "msisdn,nama\n6281234567890,",
            "msisdn,produk\n6281234567890,Tas",
            "msisdn,nama\n6281234567890,Budi\n6281234567890,Siti",
            "msisdn,nama\ninvalid,Budi",
            "msisdn,nama\n6281234567890,Budi,Extra",
            "msisdn,nama\n",
        ] as $content) {
            $this->post(route('user.whatsapp.broadcasts.store'), [
                'name' => 'Invalid', 'whatsapp_template_id' => $template->id,
                'recipients_csv' => UploadedFile::fake()->createWithContent('data.csv', $content),
            ])->assertSessionHasErrors('recipients_csv');
        }
        $this->assertDatabaseCount('whatsapp_broadcasts', 0);
    }

    public function test_csv_accepts_more_than_one_thousand_recipients(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $rows = array_map(fn ($i) => '628'.str_pad((string) $i, 9, '0', STR_PAD_LEFT), range(1, 1500));
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Large CSV', 'whatsapp_template_id' => $template->id,
            'recipients_csv' => UploadedFile::fake()->createWithContent('data.csv', "msisdn\n".implode("\n", $rows)),
        ])->assertSessionHasNoErrors();
        $this->assertSame(1500, $user->whatsappBroadcasts()->sole()->recipient_count);
    }

    private function template(User $user): WhatsappTemplate
    {
        $template = $user->whatsappTemplates()->create([
            'name' => 'promo_toko', 'language' => 'id', 'display_name' => 'Toko',
            'header_type' => 'NONE', 'body' => 'Pesan promosi', 'buttons' => [],
        ]);
        $template->forceFill(['approval_status' => WhatsappTemplate::APPROVED])->save();

        return $template;
    }

    public function test_broadcast_requires_a_template_first(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('user.whatsapp.broadcasts.index'))->assertOk()->assertSee('Belum ada template yang disetujui');
        $this->get(route('user.whatsapp.broadcasts.create'))->assertRedirect(route('user.whatsapp.broadcasts.index'));
        $this->post(route('user.whatsapp.broadcasts.store'), ['name' => 'Promo', 'recipients' => '6281234567890'])
            ->assertSessionHasErrors('whatsapp_template_id');
        $this->assertDatabaseCount('whatsapp_broadcasts', 0);
    }

    public function test_broadcast_is_saved_as_draft_with_normalized_unique_recipients(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $this->actingAs($user)->get(route('user.whatsapp.broadcasts.create', ['template' => $template->id]))
            ->assertOk()->assertSee('promo_toko');
        $this->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Promo Oktober', 'whatsapp_template_id' => $template->id,
            'recipients' => "6281234567890\n6281234567890; 6281234567891",
            'user_id' => 999, 'status' => 'sent', 'recipient_count' => 999,
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $this->assertSame(WhatsappBroadcast::STATUS_DRAFT, $broadcast->status);
        $this->assertSame(2, $broadcast->recipient_count);
        $this->assertSame(['6281234567890', '6281234567891'], $broadcast->recipients);
        $this->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertSee('Pending');
        $this->get(route('user.whatsapp.broadcasts.index'))->assertOk()->assertSee('Promo Oktober');
        $this->get(route('user.whatsapp.broadcasts.edit', $broadcast))->assertOk();
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Promo baru', 'whatsapp_template_id' => $template->id, 'recipients' => '6281234567892',
        ])->assertSessionHasNoErrors()->assertRedirect(route('user.whatsapp.broadcasts.show', $broadcast));
        $this->assertSame(1, $broadcast->fresh()->recipient_count);
    }

    public function test_foreign_templates_and_foreign_broadcasts_are_inaccessible(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $template = $this->template($owner);
        $broadcast = $owner->whatsappBroadcasts()->create([
            'name' => 'Private campaign', 'whatsapp_template_id' => $template->id,
            'recipients' => ['628123456789'], 'recipient_count' => 1,
        ]);
        $this->template($other);
        $this->actingAs($other);
        $this->get(route('user.whatsapp.broadcasts.create', ['template' => $template->id]))->assertNotFound();
        $this->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Attempt', 'whatsapp_template_id' => $template->id, 'recipients' => '628123456789',
        ])->assertSessionHasErrors('whatsapp_template_id');
        $this->get(route('user.whatsapp.broadcasts.index'))->assertOk()->assertDontSee('Private campaign');
        foreach (['show', 'edit'] as $action) {
            $this->get(route('user.whatsapp.broadcasts.'.$action, $broadcast))->assertForbidden();
        }
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [])->assertForbidden();
    }

    public function test_invalid_recipients_are_rejected_without_partial_storage(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $this->actingAs($user);
        foreach (['', '   ; , ', "6281234567890\ninvalid", '123', '+00012345678', ['6281234567890']] as $recipients) {
            $this->post(route('user.whatsapp.broadcasts.store'), [
                'name' => 'Invalid recipients', 'whatsapp_template_id' => $template->id, 'recipients' => $recipients,
            ])->assertSessionHasErrors('recipients');
        }
        $this->assertDatabaseCount('whatsapp_broadcasts', 0);
    }
}
