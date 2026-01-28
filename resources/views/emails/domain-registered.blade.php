@extends('emails.layout', [
    'headerTitle' => '🎉 Domain Registration Successful!',
    'branding' => $branding,
    'dashboardUrl' => $dashboardUrl ?? url('/dashboard'),
])

@section('content')
    <p>Congratulations! Your domain has been successfully registered and is now active.</p>
    
    <div class="info-box success">
        <h2 style="margin-top: 0; color: #10b981; font-size: 22px;">{{ $domain->name }}</h2>
        <p style="margin: 0; color: #065f46;">Your domain is now live and ready to use!</p>
    </div>
    
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value"><span class="badge badge-success">Active</span></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Registration Date:</span>
            <span class="info-value">{{ $domain->created_at->format('F j, Y g:i A') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Expires On:</span>
            <span class="info-value">{{ $domain->expires_at->format('F j, Y') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Registration Period:</span>
            <span class="info-value">{{ $domain->period ?? 1 }} Year(s)</span>
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
        
        @if(!empty($nameservers))
        <div class="info-row">
            <span class="info-label">Nameservers:</span>
            <span class="info-value">
                @foreach($nameservers as $ns)
                    {{ $ns }}<br>
                @endforeach
            </span>
        </div>
        @endif
    </div>
    
    <h3>📋 Invoice Information</h3>
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Invoice Number:</span>
            <span class="info-value">{{ $invoice->invoice_number }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Amount Paid:</span>
            <span class="info-value"><strong>${{ number_format($invoice->total, 2) }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Payment Date:</span>
            <span class="info-value">{{ $invoice->paid_at->format('F j, Y g:i A') }}</span>
        </div>
    </div>
    
    <h3>🚀 Next Steps</h3>
    <ol>
        <li><strong>Configure Nameservers:</strong> Point your domain to your hosting provider's nameservers</li>
        <li><strong>Set Up DNS Records:</strong> Add A, CNAME, MX records as needed for your website and email</li>
        <li><strong>Enable Auto-Renewal:</strong> Prevent domain expiration by enabling automatic renewal</li>
        <li><strong>Configure Email:</strong> Set up email forwarding or mailboxes for your domain</li>
        <li><strong>SSL Certificate:</strong> Secure your website with an SSL certificate</li>
    </ol>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl ?? url('/dashboard/domains/' . $domain->id) }}" class="button">
            Manage Your Domain
        </a>
    </div>
    
    <div class="info-box info">
        <p style="margin: 0;"><strong>💡 Pro Tip:</strong> Enable auto-renewal to ensure your domain never expires. You can always disable it later from your dashboard.</p>
    </div>
    
    <hr class="divider">
    
    <p style="color: #6b7280; font-size: 14px; text-align: center;">
        Thank you for choosing {{ $branding->email_sender_name ?? config('app.name') }} for your domain registration!
    </p>
@endsection
