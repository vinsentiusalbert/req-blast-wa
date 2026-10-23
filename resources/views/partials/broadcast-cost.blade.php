<article class="template-builder-card">
    <h2>Broadcast cost</h2>
    <dl><div><dt>Quoted rate</dt><dd>IDR {{ number_format($broadcast->unit_price, 0, '.', ',') }} / number</dd></div>
        <div><dt>Recipient count</dt><dd>{{ number_format($broadcast->recipient_count, 0, '.', ',') }} numbers</dd></div>
        <div><dt>{{ $broadcast->charged_at ? 'Amount charged' : 'Total cost' }}</dt><dd><strong>IDR {{ number_format($broadcast->charged_amount ?? $broadcast->estimatedCost(), 0, '.', ',') }}</strong></dd></div>
        @if($broadcast->charged_at)<div><dt>Charged at (UTC+7)</dt><dd>{{ $broadcast->charged_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</dd></div>@endif
    </dl>
    @if($broadcast->charged_at)
        <p class="field-hint">Charged once on approval. Marking the broadcast Finished does not charge it again.</p>
    @elseif(in_array($broadcast->status, ['approve', 'finish']))
        <p class="field-hint">This legacy broadcast is not charged retroactively.</p>
    @else
        <p class="field-hint">Charged on approval. Pending and rejected submissions are not charged.</p>
        @if(auth()->user()->isAdmin())
            <p class="wa-info">User balance: <strong>IDR {{ number_format($broadcast->user->balance, 0, '.', ',') }}</strong>. <a class="text-link" href="{{ route('admin.users.wallet', $broadcast->user) }}">Manage balance</a></p>
            @if($broadcast->user->balance < $broadcast->estimatedCost())<p class="notice error">The user has insufficient funds to approve this broadcast.</p>@endif
        @endif
    @endif
</article>
