@if($broadcast->status === \App\Models\WhatsappBroadcast::STATUS_REJECT)
<div class="notice error" role="alert">
    <strong>Alasan penolakan</strong>
    <p class="wa-review-comment">{{ $broadcast->rejection_reason }}</p>
    <p>Perbaiki broadcast melalui Edit Broadcast, lalu simpan untuk mengajukan ulang dengan status Pending.</p>
</div>
@endif
