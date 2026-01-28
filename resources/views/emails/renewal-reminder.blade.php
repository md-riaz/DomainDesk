@extends('emails.layout', [
    'headerTitle' => $urgencyLevel === 'critical' ? '🚨 URGENT: Domain Expiring Soon!' : ($urgencyLevel === 'high' ? '⚠️ Domain Renewal Reminder' : '⏰ Domain Renewal Notice'),
    'branding' => $branding,
    'dashboardUrl' => $dashboardUrl ?? url('/dashboard'),
])

@section('content')
    @if($urgencyLevel === 'critical')
        <div class="info-box danger">
            <h2 style="margin-top: 0; color: #dc2626;">⚠️ URGENT ACTION REQUIRED</h2>
            <p style="margin: 0; color: #991b1b; font-size: 16px;">
                <strong>Your domain expires in {{ $daysUntilExpiry }} day{{ $daysUntilExpiry != 1 ? 's' : '' }}!</strong>
                To prevent service disruption, renew your domain immediately.
            </p>
        </div>
    @elseif($urgencyLevel === 'high')
        <div class="info-box warning">
            <h2 style="margin-top: 0; color: #d97706;">⏰ Renewal Reminder</h2>
            <p style="margin: 0; color: #92400e;">
                Your domain expires in {{ $daysUntilExpiry }} days. Renew now to ensure uninterrupted service.
            </p>
        </div>
    @else
        <div class="info-box info">
            <h2 style="margin-top: 0; color: #2563eb;">📅 Renewal Notice</h2>
            <p style="margin: 0; color: #1e40af;">
                Your domain will expire in {{ $daysUntilExpiry }} days. Consider renewing early to avoid any service interruption.
            </p>
        </div>
    @endif
    
    <p>
        @if($daysUntilExpiry <= 7)
            Time is running out! 
        @endif
        Your domain registration is approaching its expiration date. To maintain ownership and prevent any service disruption, please renew your domain as soon as possible.
    </p>
    
    <div class="info-box">
        <h2 style="margin-top: 0;">{{ $domain->name }}</h2>
        
        <div class="info-row">
            <span class="info-label">Current Status:</span>
            <span class="info-value">
                @if($daysUntilExpiry <= 7)
                    <span class="badge badge-danger">Expiring Soon</span>
                @elseif($daysUntilExpiry <= 30)
                    <span class="badge badge-warning">Renewal Due</span>
                @else
                    <span class="badge badge-info">Active</span>
                @endif
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Expiration Date:</span>
            <span class="info-value"><strong>{{ $domain->expires_at->format('F j, Y') }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Days Remaining:</span>
            <span class="info-value">
                <strong style="color: {{ $daysUntilExpiry <= 7 ? '#dc2626' : ($daysUntilExpiry <= 30 ? '#d97706' : '#2563eb') }};">
                    {{ $daysUntilExpiry }} day{{ $daysUntilExpiry != 1 ? 's' : '' }}
                </strong>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Renewal Cost:</span>
            <span class="info-value"><strong>${{ number_format($renewalCost, 2) }}</strong> / year</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Auto-Renew:</span>
            <span class="info-value">
                @if($domain->auto_renew)
                    <span class="badge badge-success">Enabled</span>
                @else
                    <span class="badge badge-danger">Disabled</span>
                @endif
            </span>
        </div>
    </div>
    
    @if($daysUntilExpiry <= 7)
        <h3>🚨 What Happens If Your Domain Expires?</h3>
        <ul style="color: #dc2626;">
            <li><strong>Website Goes Offline:</strong> Your website will stop working immediately</li>
            <li><strong>Email Service Stops:</strong> All email addresses using this domain will cease functioning</li>
            <li><strong>Loss of Domain:</strong> After the grace period, your domain may be released and anyone can register it</li>
            <li><strong>Additional Costs:</strong> Domain recovery after expiration may incur extra fees</li>
            <li><strong>SEO Impact:</strong> Your search engine rankings could be negatively affected</li>
        </ul>
    @else
        <h3>💡 Why Renew Early?</h3>
        <ul>
            <li><strong>Peace of Mind:</strong> Avoid last-minute stress and potential service disruption</li>
            <li><strong>Continuous Service:</strong> Ensure your website and email remain operational</li>
            <li><strong>Protect Your Brand:</strong> Maintain ownership of your domain name</li>
            <li><strong>Avoid Recovery Fees:</strong> Domain recovery after expiration costs significantly more</li>
        </ul>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $renewalUrl ?? url('/dashboard/domains/' . $domain->id . '/renew') }}" class="button">
            {{ $daysUntilExpiry <= 7 ? 'Renew Domain Now - Time Running Out!' : 'Renew Domain Now' }}
        </a>
    </div>
    
    @if(!$domain->auto_renew)
        <div class="info-box warning">
            <h3 style="margin-top: 0;">🔄 Enable Auto-Renewal</h3>
            <p style="margin-bottom: 0;">
                Never worry about domain expiration again! Enable auto-renewal and we'll automatically renew your domain before it expires. 
                You'll receive a notification before each renewal, and you can disable it anytime.
            </p>
            <div style="text-align: center; margin-top: 15px;">
                <a href="{{ url('/dashboard/domains/' . $domain->id . '/settings') }}" class="button button-secondary">
                    Enable Auto-Renewal
                </a>
            </div>
        </div>
    @endif
    
    <hr class="divider">
    
    <h3>📋 Renewal Options</h3>
    <table>
        <thead>
            <tr>
                <th>Period</th>
                <th>Cost</th>
                <th>Savings</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1 Year</td>
                <td>${{ number_format($renewalCost, 2) }}</td>
                <td>-</td>
            </tr>
            <tr>
                <td>2 Years</td>
                <td>${{ number_format($renewalCost * 2, 2) }}</td>
                <td>Lock in current rate</td>
            </tr>
            <tr>
                <td>3 Years</td>
                <td>${{ number_format($renewalCost * 3, 2) }}</td>
                <td>Maximum protection</td>
            </tr>
        </tbody>
    </table>
    
    <p style="font-size: 14px; color: #6b7280; text-align: center;">
        Questions about renewal? Our support team is here to help!
    </p>
@endsection
