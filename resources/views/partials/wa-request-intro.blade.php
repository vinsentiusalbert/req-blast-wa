<dialog id="wa-request-intro" class="request-intro" aria-labelledby="request-intro-title" aria-describedby="request-intro-description" data-auto-open="{{ session()->pull('show_wa_request_intro', false) ? 'true' : 'false' }}">
    <div class="request-intro__body">
        <span class="request-intro__eyebrow">PORTAL GUIDE</span>
        <h2 id="request-intro-title">WhatsApp Long Number Requests</h2>
        <p id="request-intro-description">Submit and track WhatsApp Long Number campaigns in this portal. Every submission is reviewed by an administrator.</p>
        <ol class="request-intro__steps">
            <li><strong>Prepare a template</strong><span>Write your message and header, then submit the template for administrator approval.</span></li>
            <li><strong>Submit a campaign</strong><span>Choose an approved template and upload a CSV. The first column must be msisdn, with numbers starting with 62. Additional columns provide message variables.</span></li>
            <li><strong>Track the review</strong><span>Pending: awaiting review. Approved: accepted. Finished: marked complete by an administrator. If rejected, read the reason, make changes, and resubmit.</span></li>
        </ol>
        @if(auth()->user()->isAdmin())
            <p class="request-intro__note">As an administrator, start with Template Approvals or Broadcast Approvals. Filter by status and user to find submissions awaiting review.</p>
        @else
            <p class="request-intro__note">Saving a campaign submits it for review. Finished indicates that an administrator has marked it complete.</p>
        @endif
        <form method="dialog"><button class="button" autofocus>Got it</button></form>
    </div>
</dialog>
