@extends('emails.layout', [
    'headerTitle' => '📄 New Invoice Issued',
    'branding' => $branding,
    'dashboardUrl' => $dashboardUrl ?? url('/dashboard'),
])

@section('content')
    <p>A new invoice has been issued for your account. Please review the details below and submit payment by the due date.</p>
    
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Invoice Number:</span>
            <span class="info-value"><strong>{{ $invoice->invoice_number }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Issue Date:</span>
            <span class="info-value">{{ $invoice->issued_at->format('F j, Y') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Due Date:</span>
            <span class="info-value">
                <strong style="color: {{ $invoice->due_at->isPast() ? '#dc2626' : ($invoice->due_at->diffInDays(now()) <= 7 ? '#d97706' : '#111827') }};">
                    {{ $invoice->due_at->format('F j, Y') }}
                    @if($invoice->due_at->isPast())
                        <span class="badge badge-danger">OVERDUE</span>
                    @elseif($invoice->due_at->diffInDays(now()) <= 7)
                        <span class="badge badge-warning">Due Soon</span>
                    @endif
                </strong>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">
                @if($invoice->status->value === 'paid')
                    <span class="badge badge-success">Paid</span>
                @elseif($invoice->status->value === 'pending')
                    <span class="badge badge-warning">Pending</span>
                @else
                    <span class="badge badge-danger">{{ ucfirst($invoice->status->value) }}</span>
                @endif
            </span>
        </div>
    </div>
    
    <h3>📋 Invoice Details</h3>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: center;">Quantity</th>
                <th style="text-align: right;">Unit Price</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->description }}</strong>
                    @if($item->metadata)
                        <br><small style="color: #6b7280;">{{ $item->metadata }}</small>
                    @endif
                </td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right;"><strong>${{ number_format($item->total, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin: 20px 0; padding: 20px; background-color: #f9fafb; border-radius: 6px;">
        <table style="margin: 0; width: 100%;">
            <tr>
                <td style="text-align: right; padding: 8px; border: none;"><strong>Subtotal:</strong></td>
                <td style="text-align: right; padding: 8px; border: none; width: 120px;">${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->tax > 0)
            <tr>
                <td style="text-align: right; padding: 8px; border: none;"><strong>Tax:</strong></td>
                <td style="text-align: right; padding: 8px; border: none;">${{ number_format($invoice->tax, 2) }}</td>
            </tr>
            @endif
            <tr style="border-top: 2px solid #e5e7eb;">
                <td style="text-align: right; padding: 12px 8px; font-size: 18px; border: none;"><strong>Total Amount Due:</strong></td>
                <td style="text-align: right; padding: 12px 8px; font-size: 18px; border: none;"><strong style="color: #10b981;">${{ number_format($invoice->total, 2) }}</strong></td>
            </tr>
        </table>
    </div>
    
    @if($invoice->status->value !== 'paid')
        <h3>💳 Payment Instructions</h3>
        <p>You can pay this invoice using any of the following methods:</p>
        
        <div class="info-box">
            <h4 style="margin-top: 0;">Option 1: Pay Online (Recommended)</h4>
            <p>Pay securely online using your credit card or account balance.</p>
            <div style="text-align: center; margin: 15px 0;">
                <a href="{{ $paymentUrl ?? url('/dashboard/invoices/' . $invoice->id . '/pay') }}" class="button">
                    Pay Invoice Online
                </a>
            </div>
        </div>
        
        @if(!empty($branding->support_email))
        <div class="info-box">
            <h4 style="margin-top: 0;">Option 2: Bank Transfer</h4>
            <p style="margin-bottom: 5px;">For bank transfer payments, please contact our billing department:</p>
            <p style="margin: 0;">
                Email: <a href="mailto:{{ $branding->support_email }}" style="color: {{ $branding->primary_color ?? '#4f46e5' }};">{{ $branding->support_email }}</a>
            </p>
        </div>
        @endif
        
        <div class="info-box">
            <h4 style="margin-top: 0;">Option 3: Account Balance</h4>
            <p style="margin-bottom: 0;">
                @if(isset($accountBalance))
                    Current Balance: <strong>${{ number_format($accountBalance, 2) }}</strong><br>
                    @if($accountBalance >= $invoice->total)
                        You have sufficient balance in your account to pay this invoice. 
                        Click the button above to pay using your account balance.
                    @else
                        Add funds to your account balance to enable automatic invoice payment.
                    @endif
                @else
                    Add funds to your account balance to enable automatic invoice payment.
                @endif
            </p>
        </div>
        
        @if($invoice->due_at->isPast())
            <div class="info-box danger">
                <p style="margin: 0;">
                    <strong>⚠️ This invoice is overdue.</strong> To avoid service interruption, please make payment immediately. 
                    Late payment may result in suspension of services.
                </p>
            </div>
        @elseif($invoice->due_at->diffInDays(now()) <= 7)
            <div class="info-box warning">
                <p style="margin: 0;">
                    <strong>⏰ Due Soon:</strong> This invoice is due in {{ $invoice->due_at->diffInDays(now()) }} day{{ $invoice->due_at->diffInDays(now()) != 1 ? 's' : '' }}. 
                    Please make payment before the due date to avoid any service disruption.
                </p>
            </div>
        @endif
    @else
        <div class="info-box success">
            <p style="margin: 0;">
                <strong>✅ This invoice has been paid.</strong> Thank you for your payment!
            </p>
        </div>
    @endif
    
    <h3>📥 Download Invoice</h3>
    <p>You can download a PDF copy of this invoice from your dashboard for your records.</p>
    
    <div style="text-align: center; margin: 20px 0;">
        <a href="{{ url('/dashboard/invoices/' . $invoice->id . '/download') }}" class="button button-secondary">
            Download PDF Invoice
        </a>
        
        <a href="{{ url('/dashboard/invoices/' . $invoice->id) }}" class="button button-secondary">
            View Invoice Details
        </a>
    </div>
    
    <hr class="divider">
    
    <div class="info-box info">
        <h3 style="margin-top: 0;">Questions About This Invoice?</h3>
        <p style="margin-bottom: 0;">
            If you have any questions about this invoice or need assistance with payment, 
            please don't hesitate to contact our billing support team.
        </p>
    </div>
    
    <p style="font-size: 14px; color: #6b7280; text-align: center;">
        Please keep this invoice for your records. A copy is also available in your account dashboard.
    </p>
@endsection
