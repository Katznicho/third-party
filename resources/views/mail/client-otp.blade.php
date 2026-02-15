<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
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
        .otp-box {
            background-color: #f3f4f6;
            border: 2px dashed #3b82f6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .otp {
            font-size: 36px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            color: #1e40af;
            letter-spacing: 8px;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
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
            <h1 style="margin: 0; font-size: 24px;">Your Verification Code</h1>
        </div>
        
        <p>Hello{{ $clientName ? ' ' . $clientName : '' }},</p>
        
        <p>You have requested a verification code for your account. Use the code below to verify your identity:</p>
        
        <div class="otp-box">
            <p style="margin: 0 0 10px 0; font-size: 14px; color: #6b7280;">Your Verification Code:</p>
            <div class="otp">{{ $otp }}</div>
        </div>
        
        <div class="warning">
            <p style="margin: 0; color: #92400e;">
                <strong>⚠️ Important:</strong> This code will expire in {{ $expiresInMinutes }} minutes. 
                Do not share this code with anyone. If you did not request this code, please ignore this email.
            </p>
        </div>
        
        <p>Enter this code in the verification form to complete your verification process.</p>
        
        <div class="footer">
            <p style="margin: 0;">This is an automated message from {{ config('app.name') }}.</p>
            <p style="margin: 5px 0 0 0;">Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
