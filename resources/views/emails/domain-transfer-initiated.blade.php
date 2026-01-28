<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Domain Transfer Initiated</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">Domain Transfer Initiated</h2>
        
        <p>Hello,</p>
        
        <p>Your domain transfer has been successfully initiated:</p>
        
        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <strong>Domain:</strong> {{ $domainName }}<br>
            <strong>Estimated Completion:</strong> {{ $estimatedCompletion }}
        </div>
        
        <h3>What happens next?</h3>
        <ol>
            <li>The current registrar will receive the transfer request</li>
            <li>You may receive an email from the current registrar to approve the transfer</li>
            <li>The transfer will be processed automatically (typically 5-7 days)</li>
            <li>You'll receive a confirmation email once the transfer is complete</li>
        </ol>
        
        <p><strong>Important:</strong> If you did not initiate this transfer, please contact us immediately.</p>
        
        <p>You can check the transfer status at any time from your domain dashboard.</p>
        
        <p>Thank you for choosing our service!</p>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">
        <p style="font-size: 12px; color: #6b7280;">
            This is an automated email. Please do not reply to this message.
        </p>
    </div>
</body>
</html>
