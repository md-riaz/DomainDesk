@extends('emails.layout', [
    'headerTitle' => '🔄 Domain Transfer Initiated',
    'branding' => $branding,
    'dashboardUrl' => $dashboardUrl ?? url('/dashboard'),
])

@section('content')
    <div class="info-box info">
        <h2 style="margin-top: 0; color: #2563eb;">🔄 Transfer In Progress</h2>
        <p style="margin: 0; color: #1e40af; font-size: 16px;">
            Your domain transfer request has been successfully initiated and is now being processed.
        </p>
    </div>
    
    <p>
        We've received your request to transfer <strong>{{ $domain->name }}</strong> to 
        {{ $branding->email_sender_name ?? config('app.name') }}. The transfer process has begun.
    </p>
    
    <div class="info-box">
        <h2 style="margin-top: 0;">{{ $domain->name }}</h2>
        
        <div class="info-row">
            <span class="info-label">Transfer Status:</span>
            <span class="info-value"><span class="badge badge-info">{{ $status ?? 'Pending' }}</span></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Initiated On:</span>
            <span class="info-value">{{ now()->format('F j, Y g:i A') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Expected Completion:</span>
            <span class="info-value"><strong>{{ $expectedCompletion ?? now()->addDays(5)->format('F j, Y') }}</strong></span>
        </div>
    </div>
    
    <h3>⏱️ Transfer Timeline</h3>
    <p>Transfers typically complete within 5-7 days. You'll receive updates throughout the process.</p>
    
    <h3>📋 What Happens Next?</h3>
    <ol>
        <li><strong>Current Registrar Notification:</strong> Your current registrar has been notified of the transfer request</li>
        <li><strong>Approval May Be Required:</strong> Check your email for any approval requests from your current registrar</li>
        <li><strong>Processing Time:</strong> Transfers typically complete within 5-7 days</li>
        <li><strong>Completion Notification:</strong> We'll send you an email once the transfer is complete</li>
        <li><strong>Domain Activation:</strong> Your domain will be immediately available in your dashboard after transfer</li>
    </ol>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ url('/dashboard/domains/' . ($domain->id ?? 'transfers')) }}" class="button">
            Track Transfer Status
        </a>
    </div>
@endsection
