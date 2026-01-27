<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Vendor Code</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 30px;
        }
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin: -30px -30px 30px -30px;
        }
        .code-box {
            background-color: #f3f4f6;
            border: 2px dashed #3b82f6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            color: #1e40af;
            letter-spacing: 4px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">Your Third Party Vendor Code</h1>
        </div>
        
        <p>Hello,</p>
        
        <p>You have been sent the vendor code for <strong>{{ $vendorName }}</strong>.</p>
        
        @if($customMessage)
        <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-style: italic; color: #92400e;">{{ $customMessage }}</p>
        </div>
        @endif
        
        <div class="code-box">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #6b7280;">Vendor Code:</p>
            <div class="code">{{ $vendorCode }}</div>
        </div>
        
        <p>This code can be used to register your business as a client with {{ $vendorName }} in the Kashtre system.</p>
        
        <p><strong>How to use this code:</strong></p>
        <ol>
            <li>Go to the Kashtre client registration page</li>
            <li>Select "Company Client" registration type</li>
            <li>Choose "Register as Client and Third Party Vendor"</li>
            <li>Enter the vendor code above when prompted</li>
            <li>Complete the registration process</li>
        </ol>
        
        <p>If you have any questions or need assistance, please contact {{ $vendorName }} directly.</p>
        
        <div class="footer">
            <p style="margin: 0;">This is an automated message from the Kashtre system.</p>
            <p style="margin: 5px 0 0 0;">Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
