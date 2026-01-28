<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Domain Transfer Failed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #ef4444;">Domain Transfer Failed</h2>
        
        <p>Hello,</p>
        
        <p>Unfortunately, the transfer for your domain could not be completed:</p>
        
        <div style="background-color: #fef2f2; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ef4444;">
            <strong>Domain:</strong> {{ $domainName }}<br>
            <strong>Reason:</strong> {{ $reason }}
        </div>
        
        <h3>Common reasons for transfer failure:</h3>
        <ul>
            <li>Incorrect or invalid authorization code</li>
            <li>Domain is locked at the current registrar</li>
            <li>Domain is less than 60 days old</li>
            <li>Transfer was rejected by the current owner</li>
            <li>Domain has expired or is in redemption period</li>
        </ul>
        
        <p><strong>Next steps:</strong></p>
        <ol>
            <li>Verify the authorization code with your current registrar</li>
            <li>Ensure the domain is unlocked</li>
            <li>Check that WHOIS contact information is correct</li>
            <li>Try initiating the transfer again</li>
        </ol>
        
        <p>Your wallet has been refunded for this failed transfer. If you need assistance, please contact our support team.</p>
        
        <p>Thank you for your understanding.</p>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">
        <p style="font-size: 12px; color: #6b7280;">
            This is an automated email. Please do not reply to this message.
        </p>
    </div>
</body>
</html>
