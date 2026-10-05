<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappBroadcastRecipient;
use App\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WhatsAppBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_campaign_is_read_only_for_every_status_and_direct_update_method(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $template = $this->template($user);
        $this->actingAs($user)->post(route('user.whatsapp.broadcasts.store'), [
            'name' => 'Locked campaign', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', '6281234567890'),
        ])->assertSessionHasNoErrors();
        $broadcast = $user->whatsappBroadcasts()->sole();
        $originalEntryId = $broadcast->recipientEntries()->sole()->id;
        foreach (array_keys(WhatsappBroadcast::STATUS_LABELS) as $status) {
            $broadcast->forceFill(['status' => $status])->save();
            $this->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertDontSee('Edit Draft');
            $this->get(route('user.whatsapp.broadcasts.edit', $broadcast))->assertForbidden();
            foreach (['put', 'patch'] as $method) {
                $this->{$method}(route('user.whatsapp.broadcasts.update', $broadcast), [
                    'name' => 'Changed', 'whatsapp_template_id' => $template->id,
                    'recipient_file' => UploadedFile::fake()->createWithContent('replacement.csv', '6281234567891'),
                ])->assertForbidden();
            }
            $this->assertSame('Locked campaign', $broadcast->fresh()->name);
            $this->assertSame(['6281234567890'], $broadcast->fresh()->recipients);
            $this->assertSame($originalEntryId, $broadcast->recipientEntries()->sole()->id);
        }
        $this->get(route('user.whatsapp.broadcasts.index'))->assertOk()->assertSee('Lihat Campaign')->assertDontSee('Edit draft');
        $this->get(route('user.whatsapp.broadcasts.recipients.export', $broadcast))->assertOk()->assertDownload();
        $this->get(route('user.whatsapp.broadcasts.dlr.export', $broadcast))->assertOk()->assertDownload();
    }

    public function test_recipient_list_is_downloadable_for_owner_and_admin_with_legacy_fallback(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $broadcast = $user->whatsappBroadcasts()->create([
            'name' => 'Download recipients', 'whatsapp_template_id' => $this->template($user)->id,
            'recipients' => ['6281234567890', '6281234567891'], 'recipient_count' => 2,
        ]);
        foreach ([
            [$user, 'user.whatsapp.broadcasts'],
            [User::factory()->create(['role' => User::ROLE_ADMIN]), 'admin.whatsapp.campaigns'],
        ] as [$viewer, $routes]) {
            $this->actingAs($viewer)->get(route($routes.'.show', $broadcast))->assertOk()
                ->assertSee('Unduh Daftar Penerima (CSV)')->assertDontSee('id="broadcast-recipients"', false)
                ->assertDontSee('6281234567890');
            $response = $this->get(route($routes.'.recipients.export', $broadcast))
                ->assertOk()->assertDownload('penerima-broadcast-'.$broadcast->id.'.csv');
            $this->assertSame("\xEF\xBB\xBFmsisdn\n6281234567890\n6281234567891\n", $response->streamedContent());
        }
        $broadcast->recipientEntries()->create(['user_id' => $user->id, 'phone_number' => '6281234567892']);
        $this->actingAs($user);
        $csv = $this->get(route('user.whatsapp.broadcasts.recipients.export', $broadcast))->assertOk()->streamedContent();
        $this->assertStringContainsString('6281234567892', $csv);
        $this->assertStringNotContainsString('6281234567890', $csv);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]));
        $this->get(route('user.whatsapp.broadcasts.recipients.export', $broadcast))->assertForbidden();
        $this->get(route('admin.whatsapp.campaigns.recipients.export', $broadcast))->assertForbidden();
    }

    public function test_large_campaign_shows_delivery_report_without_per_recipient_message_examples(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $template = $this->template($user);
        $template->forceFill(['body' => 'Halo {{var1}}'])->save();
        $numbers = array_map(fn ($index) => '6281234'.str_pad($index, 5, '0', STR_PAD_LEFT), range(1, 7000));
        $broadcast = $user->whatsappBroadcasts()->create([
            'name' => 'Large preview', 'whatsapp_template_id' => $template->id,
            'recipients' => $numbers, 'recipient_count' => count($numbers),
        ]);
        foreach (array_chunk($numbers, 500, true) as $chunk) {
            $rows = [];
            foreach ($chunk as $index => $number) {
                $rows[] = [
                    'whatsapp_broadcast_id' => $broadcast->id, 'user_id' => $user->id,
                    'phone_number' => $number, 'variables' => json_encode(['var1' => 'Pelanggan '.($index + 1)]),
                ];
            }
            WhatsappBroadcastRecipient::insert($rows);
        }

        foreach ([
            [$user, 'user.whatsapp.broadcasts.show'],
            [User::factory()->create(['role' => User::ROLE_ADMIN]), 'admin.whatsapp.campaigns.show'],
        ] as [$viewer, $route]) {
            $this->actingAs($viewer);
            $url = route($route, $broadcast);
            $this->get($url)->assertOk()
                ->assertViewHas('dlrRows', fn ($rows) => $rows->count() === 25 && $rows->total() === 7000)
                ->assertViewHas('dlrCounts', ['pending' => 7000, 'sent' => 0, 'success' => 0, 'failed' => 0])
                ->assertSee('7.000')->assertSee('Delivery report (DLR)')->assertSee('Unduh Daftar Penerima (CSV)')
                ->assertDontSee('Contoh pesan per penerima')->assertDontSee('Halo Pelanggan 1');
            $this->get($url.'?dlr_page=280')->assertOk()
                ->assertViewHas('dlrRows', fn ($rows) => $rows->count() === 25 && $rows->last()->phone_number === '628123407000');
        }
    }

    public function test_campaign_does_not_display_csv_message_variables(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $template = $this->template($user);
        $template->forceFill(['body' => 'Halo {{var1}}'])->save();
        $broadcasts = [];
        foreach (['First', 'Second'] as $name) {
            $broadcast = $user->whatsappBroadcasts()->create([
                'name' => $name, 'whatsapp_template_id' => $template->id,
                'recipients' => ['6281234567890'], 'recipient_count' => 1,
            ]);
            $broadcast->recipientEntries()->create([
                'user_id' => $user->id, 'phone_number' => '6281234567890',
                'variables' => ['var1' => $name === 'First' ? '<script>alert(1)</script>' : 'Private second message'],
            ]);
            $broadcasts[] = $broadcast;
        }
        $this->actingAs($user)->get(route('user.whatsapp.broadcasts.show', $broadcasts[0], false).'?recipient_search=1234')
            ->assertOk()->assertDontSee('Contoh pesan per penerima')
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Private second message');
    }

    public function test_admin_lists_all_templates_and_filters_campaign_approval_and_airing(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_USER]);
        $approved = $this->template($client);
        $pending = $approved->replicate();
        $pending->forceFill(['name' => 'template_pending', 'approval_status' => WhatsappTemplate::PENDING])->save();
        foreach (['draft', 'accepted', 'processing', 'completed', 'rejected'] as $status) {
            $broadcast = $client->whatsappBroadcasts()->create([
                'name' => 'campaign_'.$status, 'whatsapp_template_id' => $approved->id,
                'recipients' => ['6281234567890'], 'recipient_count' => 1,
            ]);
            $broadcast->forceFill(['status' => $status])->save();
        }
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $templates = route('admin.whatsapp.templates.index');
        $this->get($templates)->assertOk()->assertSee($approved->name)->assertSee('template_pending');
        $this->get($templates.'?status=approved')->assertSee($approved->name)->assertDontSee('template_pending');
        $this->get($templates.'?status=not_approved')->assertSee('template_pending')->assertDontSee($approved->name);
        $campaigns = route('admin.whatsapp.campaigns.index');
        $this->get($campaigns)->assertOk()->assertSee('campaign_draft')->assertSee('campaign_processing')->assertSee('campaign_completed');
        $this->get($campaigns.'?approval=approved')->assertSee('campaign_accepted')->assertSee('campaign_processing')->assertDontSee('campaign_draft')->assertDontSee('campaign_rejected');
        $this->get($campaigns.'?approval=not_approved')->assertSee('campaign_draft')->assertSee('campaign_rejected')->assertDontSee('campaign_processing');
        $this->get($campaigns.'?airing=live')->assertSee('campaign_processing')->assertDontSee('campaign_accepted')->assertDontSee('campaign_completed');
        $this->get($campaigns.'?airing=not_live')->assertSee('campaign_accepted')->assertSee('campaign_draft')->assertDontSee('campaign_processing')->assertDontSee('campaign_completed');
        $this->get($campaigns.'?airing=finished')->assertSee('campaign_completed')->assertDontSee('campaign_processing');
        $this->get($campaigns.'?approval=approved&airing=not_live&search=accepted')->assertSee('campaign_accepted')->assertDontSee('campaign_draft');
    }

    public function test_csv_variables_render_per_recipient_and_cannot_be_replaced_after_creation(): void
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
        $this->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertDontSee('Contoh pesan per penerima');
        $this->assertSame('Halo Budi, promo Diskon.', $broadcast->recipientEntries()->first()->renderMessage($template->body));
        $this->assertSame('Halo Siti, promo Bonus.', $broadcast->recipientEntries()->orderByDesc('id')->first()->renderMessage($template->body));
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Updated', 'whatsapp_template_id' => $template->id,
        ])->assertForbidden();
        $this->assertSame('Halo Budi, promo Diskon.', $broadcast->recipientEntries()->first()->renderMessage($template->body));
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Invalid', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', "msisdn,var1\n6281234567890,Budi"),
        ])->assertForbidden();
        $this->assertSame('Personalized', $broadcast->fresh()->name);
        $this->assertSame('Halo Budi, promo Diskon.', $broadcast->recipientEntries()->first()->renderMessage($template->body));
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Replacement', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', "var2;msisdn;var1\n\"Promo, baru\";6281234567892;Andi"),
        ])->assertForbidden();
        $this->assertSame(2, $broadcast->recipientEntries()->count());
        $this->assertSame('Halo Budi, promo Diskon.', $broadcast->recipientEntries()->first()->renderMessage($template->body));
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
        $this->get(route('admin.whatsapp.campaigns.show', $broadcast))->assertOk()->assertSee('Unduh Daftar Penerima (CSV)')->assertDontSee('id="broadcast-recipients"', false);
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
        $this->get(route('user.whatsapp.broadcasts.edit', $broadcast))->assertForbidden();
        $this->put(route('user.whatsapp.broadcasts.update', $broadcast), [
            'name' => 'Promo baru', 'whatsapp_template_id' => $template->id,
            'recipient_file' => UploadedFile::fake()->createWithContent('contacts.csv', '+6281234567892'),
        ])->assertForbidden();
        $this->assertSame(2, $broadcast->fresh()->recipient_count);
        $this->assertSame('Promo Oktober', $broadcast->fresh()->name);
        $this->get(route('user.whatsapp.broadcasts.index'))->assertOk()->assertSee('Lihat Campaign')->assertDontSee('Edit draft');
        $this->get(route('user.whatsapp.broadcasts.show', $broadcast))->assertOk()->assertDontSee('Edit Draft');
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
        ])->assertForbidden();
        $this->assertDatabaseCount('whatsapp_broadcast_recipients', 2);
        $this->assertSame(['6281234567890', '6281234567891'], $broadcast->fresh()->recipients);
        $this->assertDatabaseHas('whatsapp_broadcast_recipients', [
            'whatsapp_broadcast_id' => $broadcast->id, 'user_id' => $user->id, 'phone_number' => '6281234567890',
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

    public function test_update_without_csv_is_forbidden_and_keeps_saved_recipients(): void
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
        ])->assertForbidden();
        $this->assertSame('Original', $broadcast->fresh()->name);
        $this->assertSame(['6281234567890'], $broadcast->fresh()->recipients);
        $this->assertSame('6281234567890', $broadcast->recipientEntries()->sole()->phone_number);
    }
}
