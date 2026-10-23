@if($broadcast->status === \App\Models\WhatsappBroadcast::STATUS_REJECT)
<div class="notice error" role="alert">
    <strong>Rejection reason</strong>
    <p class="wa-review-comment">{{ $broadcast->rejection_reason }}</p>
    <p>Update the broadcast using Edit Broadcast, then save it to resubmit as Pending.</p>
</div>
@endif
