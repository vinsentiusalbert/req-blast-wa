@if(in_array($broadcast->status, ['approve', 'finish']))
<section class="template-builder-card" aria-label="Campaign files">
    <h2>Campaign files</h2>
    <p>Download original messages, MSISDNs, and a reporting CSV template for this campaign. Message variables are kept as placeholders.</p>
    <p><a class="button secondary" href="{{ route('admin.whatsapp.broadcasts.download', $broadcast) }}">Download messages &amp; MSISDN (.tar.gz)</a></p>
    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.whatsapp.reports.upload', $broadcast) }}" class="login-form">
        @csrf
        <div class="field-group">
            <label class="field-label" for="report_csv">Upload report for {{ $broadcast->name }}</label>
            <input class="text-input" type="file" name="report_csv" id="report_csv" accept=".csv,text/csv" required aria-describedby="report_csv_help">
            <small id="report_csv_help">UTF-8 CSV, up to 10 MB. Columns: msisdn,status,failure_reason. Status: pending, sent, or failed. Use report-template.csv from the archive. Only listed numbers in this campaign are updated; other recipients keep their results. Delivery timestamps and attempt counts are not supplied by this format.</small>
            @error('report_csv')<p class="notice error">{{ $message }}</p>@enderror
        </div>
        <button class="button" type="submit">Upload campaign report</button>
    </form>
</section>
@endif
