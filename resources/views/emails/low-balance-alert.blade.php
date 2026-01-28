@extends('emails.layout', [
    'headerTitle' => '⚠️ Low Account Balance Alert',
    'branding' => $branding,
    'dashboardUrl' => $dashboardUrl ?? url('/dashboard'),
])

@section('content')
    <div class="info-box warning">
        <h2 style="margin-top: 0; color: #d97706;">💰 Low Balance Warning</h2>
        <p style="margin: 0; color: #92400e; font-size: 16px;">
            Your account balance is running low. Add funds to ensure uninterrupted service for your domains.
        </p>
    </div>
    
    <p>
        Your account balance has fallen below the minimum threshold. To prevent service interruption and 
        ensure automatic renewals can be processed, please add funds to your account.
    </p>
    
    <h3>💵 Account Balance</h3>
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Current Balance:</span>
            <span class="info-value">
                <strong style="color: {{ $currentBalance <= 0 ? '#dc2626' : '#d97706' }}; font-size: 20px;">
                    ${{ number_format($currentBalance, 2) }}
                </strong>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Minimum Threshold:</span>
            <span class="info-value">${{ number_format($threshold, 2) }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Recommended Balance:</span>
            <span class="info-value">${{ number_format($recommendedBalance ?? ($threshold * 2), 2) }}</span>
        </div>
        
        @if(isset($estimatedUsage))
        <div class="info-row">
            <span class="info-label">Est. Monthly Usage:</span>
            <span class="info-value">${{ number_format($estimatedUsage, 2) }}</span>
        </div>
        @endif
    </div>
    
    @if(isset($upcomingCharges) && count($upcomingCharges) > 0)
        <h3>📅 Upcoming Charges</h3>
        <p>The following charges are scheduled to be processed from your account balance:</p>
        
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Due Date</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($upcomingCharges as $charge)
                <tr>
                    <td>{{ $charge['description'] }}</td>
                    <td>{{ $charge['due_date'] }}</td>
                    <td style="text-align: right;">${{ number_format($charge['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div style="margin: 15px 0; padding: 15px; background-color: #f9fafb; border-radius: 6px; text-align: right;">
            <p style="margin: 0; font-size: 18px;">
                <strong>Total Upcoming: <span style="color: #d97706;">${{ number_format(array_sum(array_column($upcomingCharges, 'amount')), 2) }}</span></strong>
            </p>
        </div>
        
        @if($currentBalance < array_sum(array_column($upcomingCharges, 'amount')))
            <div class="info-box danger">
                <p style="margin: 0;">
                    <strong>⚠️ Insufficient Funds:</strong> Your current balance is not sufficient to cover upcoming charges. 
                    Services may be suspended if automatic renewals cannot be processed.
                </p>
            </div>
        @endif
    @endif
    
    <h3>💳 Add Funds to Your Account</h3>
    <p>Add funds now to maintain uninterrupted service and enable automatic payments:</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ url('/dashboard/wallet/add-funds') }}" class="button">
            Add Funds Now
        </a>
    </div>
    
    <h3>💰 Suggested Top-Up Amounts</h3>
    <div style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        @foreach([50, 100, 250, 500] as $amount)
        <div style="flex: 1; min-width: 120px; padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; text-align: center;">
            <p style="margin: 0; font-size: 24px; font-weight: 600; color: {{ $branding->primary_color ?? '#4f46e5' }};">
                ${{ $amount }}
            </p>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #6b7280;">
                {{ $amount <= 100 ? 'Basic' : ($amount <= 250 ? 'Standard' : 'Premium') }}
            </p>
        </div>
        @endforeach
    </div>
    
    <h3>🔄 Payment Methods</h3>
    <ul>
        <li><strong>Credit/Debit Card:</strong> Instant funding via secure card payment</li>
        <li><strong>Bank Transfer:</strong> Transfer funds directly from your bank account</li>
        <li><strong>PayPal:</strong> Use your PayPal balance or linked accounts</li>
        <li><strong>Cryptocurrency:</strong> Bitcoin and other cryptocurrencies accepted</li>
    </ul>
    
    <h3>✨ Benefits of Maintaining Balance</h3>
    <div class="info-box success">
        <ul style="margin: 10px 0;">
            <li><strong>Automatic Renewals:</strong> Domains renew automatically without manual payment</li>
            <li><strong>No Service Interruption:</strong> Avoid downtime from failed payment attempts</li>
            <li><strong>Instant Transactions:</strong> Purchase and renew domains immediately</li>
            <li><strong>Better Management:</strong> Simplified billing with prepaid balance</li>
            <li><strong>Peace of Mind:</strong> Know your domains are always protected</li>
        </ul>
    </div>
    
    @if(isset($activeDomains) && $activeDomains > 0)
        <div class="info-box info">
            <h3 style="margin-top: 0;">📊 Your Account</h3>
            <div class="info-row">
                <span class="info-label">Active Domains:</span>
                <span class="info-value"><strong>{{ $activeDomains }}</strong></span>
            </div>
            @if(isset($autoRenewEnabled))
            <div class="info-row">
                <span class="info-label">Domains with Auto-Renew:</span>
                <span class="info-value"><strong>{{ $autoRenewEnabled }}</strong></span>
            </div>
            @endif
            @if(isset($nextRenewal))
            <div class="info-row">
                <span class="info-label">Next Renewal:</span>
                <span class="info-value">{{ $nextRenewal['date'] }} - {{ $nextRenewal['domain'] }}</span>
            </div>
            @endif
        </div>
    @endif
    
    <hr class="divider">
    
    <div class="info-box warning">
        <h3 style="margin-top: 0;">⚠️ Important Notice</h3>
        <p style="margin-bottom: 0;">
            If your balance remains below the threshold and automatic renewals fail due to insufficient funds, 
            your domains may expire, resulting in service disruption. We recommend maintaining a balance sufficient 
            to cover at least 3 months of typical usage.
        </p>
    </div>
    
    <p style="text-align: center; margin-top: 30px;">
        <a href="{{ url('/dashboard/wallet') }}" style="color: {{ $branding->primary_color ?? '#4f46e5' }};">
            View Account Balance & Transaction History
        </a>
    </p>
@endsection
