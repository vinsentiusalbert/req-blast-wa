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

    public function test_csv_variables_render_per_recipient_and_survive_edit_without_upload(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $template = $this->template($user);
        $template->forceFill(['body' => 'Halo {{var1}}, promo {{ var2 }}.'])->save();
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Personalized', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', "msisdn,var1,var2\n081234567890,Budi,Diskon\n6281234567890,Duplikat,Abaikan\n6281234567891,Siti,Bonus"),
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $this->assertSame(2, $broadcast->recipient_count);
        $this->assertSame(['var1' => 'Budi', 'var2' => 'Diskon'], $broadcast->recipientEntries()->first()->variables);
        $this->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertSee('Halo Budi, promo Diskon.')->assertSee('Halo Siti, promo Bonus.');
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Updated', 'whatsapp_template_id' => $template->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Halo Budi, promo Diskon.', $broadcast->recipientEntries()->first()->renderMessage($template->body));
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Invalid', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', "msisdn,var1\n6281234567890,Budi"),
        ])->assertSessionHasErrors('recipient_file');
        $this->assertSame('Updated', $broadcast->fresh()->name);
        $this->assertSame('Halo Budi, promo Diskon.', $broadcast->recipientEntries()->first()->renderMessage($template->body));
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Replacement', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', "var2;msisdn;var1\n\"Promo, baru\";6281234567892;Andi"),
        ])->assertSessionHasNoErrors();
        $this->assertSame('Halo Andi, promo Promo, baru.', $broadcast->recipientEntries()->sole()->renderMessage($template->body));
    }

    public function test_admin_can_manage_campaign_status_and_client_sees_it(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_USER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $template = $this->template($client);
        $broadcast = $client->whatsappBroadcasts()->create([
            'name' => 'Campaign client', 'whatsapp_template_id' => $template->id,
            'recipients' => ['6281234567890'], 'recipient_count' => 1,
        ]);
        $this->actingAs($admin)->get(route('admin.whatsapp.campaigns.index'))->assertOk()->assertSee('Campaign client');
        $this->get(route('admin.whatsapp.campaigns.show', $broadcast))->assertOk()->assertSee('6281234567890');
        $this->patch(route('admin.whatsapp.campaigns.update', $broadcast), ['status' => 'accepted'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.whatsapp.campaigns.show', $broadcast));
        $this->assertSame('accepted', $broadcast->fresh()->status);
        $this->patch(route('admin.whatsapp.campaigns.update', $broadcast), ['status' => 'invalid'])->assertSessionHasErrors('status');
        $this->actingAs($client)->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertSee('Diterima')->assertDontSee('Edit Draft');
        $this->get(route('user.whatsapp.broadcasts.index'))->assertOk()->assertSee('Diterima');
        $this->get(route('admin.whatsapp.campaigns.index'))->assertForbidden();
        $this->patch(route('admin.whatsapp.campaigns.update', $broadcast), ['status' => 'completed'])->assertForbidden();
        $this->get(route('user.whatsapp.broadcasts.edit', $broadcast))->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.whatsapp.campaigns.update', $broadcast), ['status' => 'draft'])->assertSessionHasNoErrors();
        $template->forceFill(['approval_status' => WhatsappTemplate::PENDING])->save();
        $this->patch(route('admin.whatsapp.campaigns.update', $broadcast), ['status' => 'accepted'])->assertSessionHasErrors('status');
        $this->assertSame('draft', $broadcast->fresh()->status);
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
        $this->post(route('user.whatsapp.broadcasts.store'), ['name' => 'Promo', 'recipients' => '081234567890'])
            ->assertSessionHasErrors('whatsapp_template_id');
        $this->assertDatabaseCount('whatsapp_broadcasts', 0);
    }

    public function test_broadcast_is_saved_as_draft_with_normalized_unique_recipients(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $this->actingAs($user)->get(route('user.whatsapp.broadcasts.create', ['template' => $template->id]))
            ->assertOk()->assertSee('promo_toko')->assertSee('accept=".csv"', false)->assertDontSee('name="recipients"', false);
        $this->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Promo Oktober', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', "0812-3456-7890\n+6281234567890\n6281234567891"),
            'user_id' => 999, 'status' => 'sent', 'recipient_count' => 999,
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $this->assertSame(WhatsappBroadcast::STATUS_DRAFT, $broadcast->status);
        $this->assertSame(2, $broadcast->recipient_count);
        $this->assertSame(['6281234567890', '6281234567891'], $broadcast->recipients);
        $this->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertSee('Belum dikirim');
        $this->get(route('user.whatsapp.broadcasts.index'))->assertOk()->assertSee('Promo Oktober');
        $this->get(route('user.whatsapp.broadcasts.edit', $broadcast))->assertOk();
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Promo baru', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', '+6281234567892'),
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
            'name' => 'Attempt', 'whatsapp_template_id' => $template->id, 'recipients' => '08123456789',
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
        foreach (['', '   ; , ', "081234567890\ninvalid", '123', '+00012345678', ['081234567890']] as $recipients) {
            $this->post(route('user.whatsapp.broadcasts.store'), [
                'name' => 'Invalid recipients', 'whatsapp_template_id' => $template->id, 'recipients' => $recipients,
            ])->assertSessionHasErrors('recipient_file');
        }
        $this->assertDatabaseCount('whatsapp_broadcasts', 0);
    }

    public function test_csv_import_saves_broadcast_and_owner_ids(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'CSV import', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', "\xEF\xBB\xBFnama;nomor\nPelanggan;081234567890\nLain;+6281234567891\n"),
            'user_id' => 999, 'whatsapp_broadcast_id' => 999,
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $this->assertSame(2, $broadcast->recipient_count);
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 2);
        foreach (['6281234567890', '6281234567891'] as $number) {
            $this->assertDatabaseHas('whatsapp_broadcast_recipients', [
                'whatsapp_broadcast_id' => $broadcast->id, 'user_id' => $user->id, 'phone_number' => $number,
            ]);
        }
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'CSV replacement', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', "081234567892\n+6281234567892"),
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 1);
        $this->assertSame(['6281234567892'], $broadcast->fresh()->recipients);
        $this->assertDatabaseHas('whatsapp_broadcast_recipients', [
            'whatsapp_broadcast_id' => $broadcast->id, 'user_id' => $user->id, 'phone_number' => '6281234567892',
        ]);
    }

    public function test_invalid_imports_do_not_save_partial_data(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $this->actingAs($user);
        foreach ([
            ['contacts.csv', "nomor\n081234567890\ninvalid"],
            ['contacts.csv', ''],
            ['contacts.txt', '081234567890'],
            ['contacts.csv', "nama,email\nPerson,person@example.com"],
            ['contacts.exe', '081234567890'],
        ] as [$filename, $content]) {
            $this->post(route('user.whatsapp.broadcasts.store'), [
                'name' => 'Invalid import', 'whatsapp_template_id' => $template->id,
                'recipient_file' => UploadedFile::fake()->createWithContent($filename, $content),
            ])->assertSessionHasErrors('recipient_file');
        }
        $this->assertDatabaseCount('whatsapp_broadcasts', 0);
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 0);
    }

    public function test_manual_input_is_rejected_even_with_a_csv(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Manual attempt', 'whatsapp_template_id' => $template->id,
            'recipients' => '081234567890',
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', '081234567891'),
        ])->assertSessionHasErrors('recipients');
        $this->assertDatabaseCount('whatsapp_broadcasts', 0);
    }

    public function test_csv_with_more_than_one_thousand_recipients_is_saved(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $numbers = array_map(fn ($index) => '6281234'.str_pad($index, 5, '0', STR_PAD_LEFT), range(1, 1001));
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Large broadcast', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', implode("\n", $numbers)),
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $this->assertSame(1001, $broadcast->recipient_count);
        $this->assertSame($numbers, $broadcast->recipients);
        $this->assertSame(1001, $broadcast->recipientEntries()->where('user_id', $user->id)->count());
    }

    public function test_edit_without_csv_keeps_saved_recipients(): void
    {
        $user = User::factory()->create();
        $template = $this->template($user);
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Original', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', '081234567890'),
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Renamed', 'whatsapp_template_id' => $template->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame(['6281234567890'], $broadcast->fresh()->recipients);
        $this->assertSame('6281234567890', $broadcast->recipientEntries()->sole()->phone_number);
    }
}
