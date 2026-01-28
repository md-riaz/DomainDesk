@extends('emails.layout', [
    'headerTitle' => '✅ Domain Renewal Successful!',
    'branding' => $branding,
    'dashboardUrl' => $dashboardUrl ?? url('/dashboard'),
])

@section('content')
    <p>Great news! Your domain has been successfully renewed and your registration has been extended.</p>
    
    <div class="info-box success">
        <h2 style="margin-top: 0; color: #10b981; font-size: 22px;">{{ $domain->name }}</h2>
        <p style="margin: 0; color: #065f46;">Your domain is secured for another {{ $renewalPeriod ?? 1 }} year(s)!</p>
    </div>
    
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value"><span class="badge badge-success">Active</span></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Previous Expiry Date:</span>
            <span class="info-value">{{ $previousExpiryDate }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">New Expiry Date:</span>
            <span class="info-value"><strong>{{ $domain->expires_at->format('F j, Y') }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Renewal Period:</span>
            <span class="info-value">{{ $renewalPeriod ?? 1 }} Year(s)</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Renewal Date:</span>
            <span class="info-value">{{ now()->format('F j, Y g:i A') }}</span>
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
    
    <h3>📋 Renewal Invoice</h3>
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Invoice Number:</span>
            <span class="info-value">{{ $invoice->invoice_number }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Renewal Cost:</span>
            <span class="info-value"><strong>${{ number_format($invoice->total, 2) }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Payment Date:</span>
            <span class="info-value">{{ $invoice->paid_at->format('F j, Y g:i A') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Payment Method:</span>
            <span class="info-value">{{ $paymentMethod ?? 'Account Balance' }}</span>
        </div>
    </div>
    
    <h3>✨ What's Included</h3>
    <ul>
        <li>Your domain registration has been extended until <strong>{{ $domain->expires_at->format('F j, Y') }}</strong></li>
        <li>All domain settings, nameservers, and DNS records remain unchanged</li>
        <li>No service interruption - your website and email continue working normally</li>
        <li>Full access to domain management tools in your dashboard</li>
    </ul>
    
    @if(!$domain->auto_renew)
        <div class="info-box warning">
            <p style="margin: 0;"><strong>⚠️ Auto-Renewal Not Enabled:</strong> To ensure uninterrupted service, we recommend enabling auto-renewal for this domain. This will automatically renew your domain before expiration.</p>
        </div>
    @else
        <div class="info-box success">
            <p style="margin: 0;"><strong>✅ Auto-Renewal Enabled:</strong> Your domain will automatically renew before the expiration date. We'll notify you before each renewal.</p>
        </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl ?? url('/dashboard/domains/' . $domain->id) }}" class="button">
            View Domain Details
        </a>
        
        <a href="{{ url('/dashboard/invoices/' . $invoice->id) }}" class="button button-secondary">
            View Invoice
        </a>
    </div>
    
    <hr class="divider">
    
    <p style="color: #6b7280; font-size: 14px; text-align: center;">
        Thank you for continuing to trust {{ $branding->email_sender_name ?? config('app.name') }} with your domain!
    </p>
@endsection
