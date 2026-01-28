@extends('emails.layout', [
    'headerTitle' => '✅ Payment Received - Thank You!',
    'branding' => $branding,
    'dashboardUrl' => $dashboardUrl ?? url('/dashboard'),
])

@section('content')
    <div class="info-box success">
        <h2 style="margin-top: 0; color: #10b981; font-size: 22px;">💚 Payment Successful!</h2>
        <p style="margin: 0; color: #065f46; font-size: 16px;">
            Your payment has been successfully processed and your invoice has been marked as paid.
        </p>
    </div>
    
    <p>Thank you for your payment! This email confirms that we have received your payment and it has been applied to your account.</p>
    
    <h3>💳 Payment Details</h3>
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Transaction ID:</span>
            <span class="info-value"><strong>{{ $transaction->id ?? 'N/A' }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Payment Date:</span>
            <span class="info-value">{{ $paymentDate->format('F j, Y g:i A') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Amount Paid:</span>
            <span class="info-value"><strong style="color: #10b981; font-size: 18px;">${{ number_format($amount, 2) }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Payment Method:</span>
            <span class="info-value">{{ $paymentMethod }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value"><span class="badge badge-success">Completed</span></span>
        </div>
    </div>
    
    <h3>📄 Invoice Information</h3>
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Invoice Number:</span>
            <span class="info-value"><strong>{{ $invoice->invoice_number }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Invoice Date:</span>
            <span class="info-value">{{ $invoice->issued_at->format('F j, Y') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Description:</span>
            <span class="info-value">
                @if($invoice->items->count() === 1)
                    {{ $invoice->items->first()->description }}
                @else
                    {{ $invoice->items->count() }} item(s)
                @endif
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Total Amount:</span>
            <span class="info-value"><strong>${{ number_format($invoice->total, 2) }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Invoice Status:</span>
            <span class="info-value"><span class="badge badge-success">Paid</span></span>
        </div>
    </div>
    
    @if(isset($services) && count($services) > 0)
        <h3>✨ Services Activated</h3>
        <p>The following services have been activated or renewed as a result of this payment:</p>
        <ul>
            @foreach($services as $service)
                <li><strong>{{ $service }}</strong></li>
            @endforeach
        </ul>
    @endif
    
    <h3>🧾 Receipt</h3>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">${{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin: 20px 0; padding: 15px; background-color: #f9fafb; border-radius: 6px; text-align: right;">
        @if($invoice->tax > 0)
            <p style="margin: 5px 0;">Subtotal: ${{ number_format($invoice->subtotal, 2) }}</p>
            <p style="margin: 5px 0;">Tax: ${{ number_format($invoice->tax, 2) }}</p>
        @endif
        <p style="margin: 5px 0; font-size: 18px;"><strong>Total Paid: <span style="color: #10b981;">${{ number_format($invoice->total, 2) }}</span></strong></p>
    </div>
    
    @if(isset($accountBalance))
        <div class="info-box info">
            <div class="info-row">
                <span class="info-label">Current Account Balance:</span>
                <span class="info-value"><strong>${{ number_format($accountBalance, 2) }}</strong></span>
            </div>
        </div>
    @endif
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ url('/dashboard/invoices/' . $invoice->id . '/download') }}" class="button">
            Download Receipt (PDF)
        </a>
        
        <a href="{{ url('/dashboard/invoices/' . $invoice->id) }}" class="button button-secondary">
            View Invoice Details
        </a>
    </div>
    
    <hr class="divider">
    
    <h3>📊 Account Summary</h3>
    <div class="info-box">
        @if(isset($totalPaid))
        <div class="info-row">
            <span class="info-label">Total Paid (All Time):</span>
            <span class="info-value">${{ number_format($totalPaid, 2) }}</span>
        </div>
        @endif
        
        @if(isset($outstandingBalance) && $outstandingBalance > 0)
        <div class="info-row">
            <span class="info-label">Outstanding Balance:</span>
            <span class="info-value" style="color: #d97706;"><strong>${{ number_format($outstandingBalance, 2) }}</strong></span>
        </div>
        @else
        <div class="info-row">
            <span class="info-label">Outstanding Balance:</span>
            <span class="info-value"><span class="badge badge-success">$0.00</span></span>
        </div>
        @endif
        
        @if(isset($accountBalance))
        <div class="info-row">
            <span class="info-label">Available Balance:</span>
            <span class="info-value"><strong>${{ number_format($accountBalance, 2) }}</strong></span>
        </div>
        @endif
    </div>
    
    @if(isset($outstandingBalance) && $outstandingBalance > 0)
        <div class="info-box warning">
            <p style="margin: 0;">
                <strong>📋 Outstanding Invoices:</strong> You have ${{ number_format($outstandingBalance, 2) }} in outstanding invoices. 
                <a href="{{ url('/dashboard/invoices') }}" style="color: {{ $branding->primary_color ?? '#4f46e5' }};">View all invoices</a>
            </p>
        </div>
    @endif
    
    <div class="info-box success">
        <h3 style="margin-top: 0;">🙏 Thank You!</h3>
        <p style="margin-bottom: 0;">
            We appreciate your business and prompt payment. If you have any questions about this payment or your account, 
            please don't hesitate to contact our support team.
        </p>
    </div>
    
    <p style="font-size: 14px; color: #6b7280; text-align: center;">
        This receipt serves as confirmation of your payment. Please save it for your records.<br>
        A copy is also available in your account dashboard at any time.
    </p>
@endsection
