<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your NaSeRy verification code</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f7f7; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="width:100%; background-color:#f3f7f7; margin:0; padding:0;">
        <tr>
            <td align="center" style="padding:36px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                    style="width:100%; max-width:600px; background:#ffffff; border-radius:20px; overflow:hidden; border:1px solid #e5e7eb;">

                    <tr>
                        <td style="background:#285F6b; padding:26px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td valign="middle">
                                                    <div style="
                                                        width:44px;
                                                        height:44px;
                                                        line-height:44px;
                                                        text-align:center;
                                                        border-radius:12px;
                                                        background:#ffffff;
                                                        color:#285F6b;
                                                        font-size:18px;
                                                        font-weight:800;
                                                    ">
                                                        N
                                                    </div>
                                                </td>

                                                <td valign="middle" style="padding-left:13px;">
                                                    <div style="font-size:19px; line-height:24px; font-weight:800; color:#ffffff;">
                                                        NaSeRy
                                                    </div>
                                                    <div style="margin-top:2px; font-size:12px; line-height:18px; color:#d6e8eb;">
                                                        {{ $purpose === 'account' ? 'Secure Account Registration' : 'Secure Event Registration' }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <td align="right" valign="middle">
                                        <span style="
                                            display:inline-block;
                                            padding:7px 11px;
                                            border-radius:999px;
                                            background:#ffffff1a;
                                            color:#ffffff;
                                            font-size:11px;
                                            font-weight:700;
                                            letter-spacing:.04em;
                                        ">
                                            EMAIL VERIFICATION
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:34px 32px 14px;">
                            <div style="font-size:25px; line-height:32px; font-weight:800; color:#111827;">
                                Verify your email address
                            </div>

                            <div style="margin-top:10px; font-size:14px; line-height:22px; color:#6b7280;">
                                You're almost done. Use the one-time PIN below to verify your email and continue {{ $purpose === 'account' ? 'creating your account' : 'your event registration' }}.
                            </div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:24px; border-collapse:separate;">
                                <tr>
                                    <td style="
                                        padding:16px 18px;
                                        border-radius:14px;
                                        background:#f6f8f8;
                                        border:1px solid #e5e7eb;
                                    ">
                                        <div style="font-size:11px; font-weight:700; letter-spacing:.10em; color:#6b7280;">
                                            {{ $purpose === 'account' ? 'VERIFYING' : 'REGISTERING FOR' }}
                                        </div>

                                        <div style="margin-top:5px; font-size:17px; line-height:24px; font-weight:800; color:#111827;">
                                            {{ $eventName }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:12px 32px 10px;">
                            <div style="
                                padding:24px 18px;
                                border-radius:18px;
                                background:#eef7f8;
                                border:1px solid #cfe3e6;
                                text-align:center;
                            ">
                                <div style="
                                    font-size:11px;
                                    line-height:16px;
                                    font-weight:800;
                                    letter-spacing:.14em;
                                    color:#285F6b;
                                ">
                                    YOUR 6-DIGIT VERIFICATION PIN
                                </div>

                                <div style="
                                    margin-top:14px;
                                    font-size:38px;
                                    line-height:46px;
                                    font-weight:800;
                                    letter-spacing:10px;
                                    color:#163a42;
                                    font-family:'Courier New', Courier, monospace;
                                ">
                                    {{ $code }}
                                </div>

                                <div style="margin-top:12px; font-size:12px; line-height:18px; color:#64748b;">
                                    This PIN expires in <strong style="color:#285F6b;">10 minutes</strong>.
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:14px 32px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="background:#fffbeb; border:1px solid #fde68a; border-radius:14px;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <div style="font-size:13px; line-height:20px; font-weight:700; color:#92400e;">
                                            Keep this code private
                                        </div>

                                        <div style="margin-top:3px; font-size:12px; line-height:19px; color:#a16207;">
                                            NaSeRy will never ask you to share this PIN with another person. If you did not request this verification, simply ignore this email.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px; background:#f8fafc; border-top:1px solid #e5e7eb;">
                            <div style="font-size:12px; line-height:18px; color:#94a3b8; text-align:center;">
                                This automated message was sent by <strong style="color:#64748b;">NaSeRy Event Management</strong>.<br>
                                Please do not reply with your verification PIN.
                            </div>
                        </td>
                    </tr>
                </table>

                <div style="max-width:600px; margin-top:16px; font-size:11px; line-height:17px; color:#9ca3af; text-align:center;">
                    © {{ date('Y') }} NaSeRy. Secure event registration and ticketing.
                </div>
            </td>
        </tr>
    </table>
</body>

</html>
