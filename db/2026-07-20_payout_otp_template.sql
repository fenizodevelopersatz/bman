-- Insert Payout OTP Verification Email Template
INSERT INTO `email_template` (`subject`, `temp_content`, `temp_name`, `temp_status`)
VALUES (
  'Verify Your Payout Request - OTP',
  '<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
    .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
    .content { padding: 20px; }
    .otp-box { background-color: #f0f0f0; padding: 15px; text-align: center; border-radius: 5px; margin: 20px 0; }
    .otp-code { font-size: 32px; font-weight: bold; letter-spacing: 2px; color: #4CAF50; }
    .details { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0; }
    .footer { text-align: center; font-size: 12px; color: #666; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2>Payout Request Verification</h2>
    </div>

    <div class="content">
      <p>Hello <strong>[USERNAME]</strong>,</p>

      <p>You have initiated a payout request. To complete this request, please verify using the One-Time Password (OTP) below:</p>

      <div class="otp-box">
        <p>Your OTP Code:</p>
        <div class="otp-code">[OTP]</div>
        <p style="color: #666; font-size: 12px;">Valid for 15 minutes</p>
      </div>

      <div class="details">
        <p><strong>Payout Details:</strong></p>
        <p>Amount: <strong>[AMOUNT]</strong></p>
        <p>Method: <strong>[METHOD]</strong></p>
      </div>

      <p style="color: #d9534f;"><strong>Security Notice:</strong></p>
      <ul>
        <li>Do not share this OTP with anyone</li>
        <li>This OTP is valid for 15 minutes only</li>
        <li>If you did not initiate this request, please ignore this email</li>
      </ul>

      <p>If you have any questions, please contact our support team.</p>
    </div>

    <div class="footer">
      <p>&copy; 2026 Nexman. All rights reserved.</p>
      <p>This is an automated message, please do not reply.</p>
    </div>
  </div>
</body>
</html>',
  'Payout OTP Verification',
  1
);
