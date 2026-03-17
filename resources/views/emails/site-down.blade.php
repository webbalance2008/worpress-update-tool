<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0e1822; color: #ffffff; padding: 40px 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #131f2c; border-radius: 8px; padding: 32px; border: 1px solid rgba(255,255,255,0.08); }
        .alert-badge { display: inline-block; background: #d4183d; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 22px; margin: 20px 0 8px; }
        .site-url { color: rgba(255,255,255,0.6); font-size: 14px; }
        .details { background: #0e1822; border-radius: 6px; padding: 16px; margin: 24px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .label { color: rgba(255,255,255,0.6); }
        .value { color: #ffffff; font-weight: 500; }
        .value.fail { color: #d4183d; }
        .value.pass { color: #10b981; }
        .footer { margin-top: 24px; font-size: 12px; color: rgba(255,255,255,0.4); text-align: center; }
        a { color: #00d4e8; }
    </style>
</head>
<body>
    <div class="container">
        <span class="alert-badge">Site Down</span>

        <h1>{{ $site->name }}</h1>
        <p class="site-url">{{ $site->url }}</p>

        <div class="details">
            <div class="detail-row">
                <span class="label">Status</span>
                <span class="value fail">{{ ucfirst($healthCheck->status->value) }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Summary</span>
                <span class="value">{{ $healthCheck->summary }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Checked At</span>
                <span class="value">{{ $healthCheck->created_at->format('d M Y H:i:s') }} UTC</span>
            </div>

            @if($healthCheck->update_job_id)
                <div class="detail-row">
                    <span class="label">Related Job</span>
                    <span class="value">#{{ $healthCheck->update_job_id }}</span>
                </div>
            @endif

            @foreach($healthCheck->checks ?? [] as $name => $check)
                <div class="detail-row">
                    <span class="label">{{ ucfirst(str_replace('_', ' ', $name)) }}</span>
                    <span class="value {{ ($check['passed'] ?? false) ? 'pass' : 'fail' }}">
                        {{ ($check['passed'] ?? false) ? 'Passed' : 'Failed' }}
                        @if(isset($check['status_code']))
                            ({{ $check['status_code'] }})
                        @endif
                    </span>
                </div>
            @endforeach
        </div>

        <p style="font-size: 14px; color: rgba(255,255,255,0.7);">
            Please check this site immediately. You can view full details in the
            <a href="{{ url('/sites/' . $site->id) }}">WP Update Manager dashboard</a>.
        </p>

        <div class="footer">
            WP Update Manager &middot; Automated Alert
        </div>
    </div>
</body>
</html>
