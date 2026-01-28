@extends('emails.layout', [
    'headerTitle' => '✅ Domain Transfer Complete!',
    'branding' => $branding,
    'dashboardUrl' => $dashboardUrl ?? url('/dashboard'),
])

@section('content')
    <div class="info-box success">
        <h2 style="margin-top: 0; color: #10b981; font-size: 24px;">🎉 Transfer Successful!</h2>
        <p style="margin: 0; color: #065f46; font-size: 16px;">
            Your domain transfer has been completed successfully. Welcome to {{ $branding->email_sender_name ?? config('app.name') }}!
        </p>
    </div>
    
    <p>
        Great news! The transfer of <strong>{{ $domain->name }}</strong> has been completed successfully. 
        Your domain is now active in your account and ready to manage.
    </p>
    
    <div class="info-box">
        <h2 style="margin-top: 0;">{{ $domain->name }}</h2>
        
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value"><span class="badge badge-success">Active</span></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Transfer Completed:</span>
            <span class="info-value">{{ now()->format('F j, Y g:i A') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">New Expiry Date:</span>
            <span class="info-value"><strong>{{ $domain->expires_at->format('F j, Y') }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Registration Extended:</span>
            <span class="info-value">+1 Year (included in transfer)</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Auto-Renew:</span>
            <span class="info-value">
                @if($domain->auto_renew)
                    <span class="badge badge-success">Enabled</span>
                @else
                    <span class="badge badge-warning">Disabled</span>
                @endif
            </span>
        </div>
    </div>
    
    <h3>🚀 Next Steps</h3>
    <ol>
        <li><strong>Verify Nameservers:</strong> Check that your nameservers are correctly configured</li>
        <li><strong>Review DNS Settings:</strong> Ensure all DNS records are properly set up</li>
        <li><strong>Enable Auto-Renewal:</strong> Prevent domain expiration by enabling automatic renewal</li>
        <li><strong>Update Contact Information:</strong> Verify your domain contact details are current</li>
        <li><strong>Configure Domain Settings:</strong> Explore all available domain management features</li>
    </ol>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ url('/dashboard/domains/' . $domain->id) }}" class="button">
            Manage Your Domain
        </a>
    </div>
    
    <h3>✨ What You Can Do Now</h3>
    <ul>
        <li><strong>DNS Management:</strong> Create and modify A, CNAME, MX, TXT, and other DNS records</li>
        <li><strong>Nameserver Configuration:</strong> Set custom nameservers for your domain</li>
        <li><strong>Auto-Renewal:</strong> Enable automatic renewal to never worry about expiration</li>
        <li><strong>Domain Locking:</strong> Protect your domain from unauthorized transfers</li>
        <li><strong>WHOIS Privacy:</strong> Enable privacy protection (where available)</li>
        <li><strong>Contact Management:</strong> Update registrant, admin, and technical contacts</li>
    </ul>
    
    @if(!$domain->auto_renew)
        <div class="info-box warning">
            <h3 style="margin-top: 0;">🔄 Enable Auto-Renewal</h3>
            <p style="margin-bottom: 0;">
                Protect your domain from accidental expiration! Enable auto-renewal and we'll automatically 
                renew your domain before it expires. You'll be notified before each renewal.
            </p>
            <div style="text-align: center; margin-top: 15px;">
                <a href="{{ url('/dashboard/domains/' . $domain->id . '/settings') }}" class="button button-secondary">
                    Enable Auto-Renewal
                </a>
            </div>
        </div>
    @endif
    
    <hr class="divider">
    
    <div class="info-box info">
        <h3 style="margin-top: 0;">💡 Transfer Benefits</h3>
        <ul style="margin: 10px 0;">
            <li>Your domain registration has been extended by 1 year at no extra cost</li>
            <li>Full access to our advanced domain management tools</li>
            <li>Professional support from our expert team</li>
            <li>Competitive renewal rates locked in</li>
            <li>Enhanced security features available</li>
        </ul>
    </div>
    
    <p style="text-align: center; color: #6b7280; font-size: 14px;">
        Thank you for transferring your domain to {{ $branding->email_sender_name ?? config('app.name') }}!<br>
        We're committed to providing you with the best domain management experience.
    </p>
@endsection
