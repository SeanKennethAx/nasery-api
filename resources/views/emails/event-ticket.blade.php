<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        Your NaSeRy Event Ticket
    </title>
</head>

<body
    style="
        margin:0;
        padding:0;
        background:#f3f7f7;
        font-family:Arial, Helvetica, sans-serif;
        color:#111827;
    ">
    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="
            width:100%;
            margin:0;
            padding:0;
            background:#f3f7f7;
        ">
        <tr>
            <td
                align="center"
                style="padding:36px 16px;">
                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    border="0"
                    style="
                        width:100%;
                        max-width:600px;
                        background:#ffffff;
                        border:1px solid #e5e7eb;
                        border-radius:20px;
                        overflow:hidden;
                    ">
                    <!-- Header -->
                    <tr>
                        <td
                            style="
                                padding:26px 32px;
                                background:#285F6b;
                            ">
                            <table
                                role="presentation"
                                width="100%"
                                cellspacing="0"
                                cellpadding="0"
                                border="0">
                                <tr>
                                    <td valign="middle">
                                        <table
                                            role="presentation"
                                            cellspacing="0"
                                            cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td valign="middle">
                                                    <div
                                                        style="
                                                            width:44px;
                                                            height:44px;
                                                            line-height:44px;
                                                            border-radius:12px;
                                                            background:#ffffff;
                                                            color:#285F6b;
                                                            font-size:18px;
                                                            font-weight:800;
                                                            text-align:center;
                                                        ">
                                                        N
                                                    </div>
                                                </td>

                                                <td
                                                    valign="middle"
                                                    style="padding-left:13px;">
                                                    <div
                                                        style="
                                                            font-size:19px;
                                                            font-weight:800;
                                                            color:#ffffff;
                                                        ">
                                                        NaSeRy
                                                    </div>

                                                    <div
                                                        style="
                                                            margin-top:3px;
                                                            font-size:12px;
                                                            color:#d6e8eb;
                                                        ">
                                                        Event Ticket
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <td
                                        align="right"
                                        valign="middle">
                                        <span
                                            style="
                                                display:inline-block;
                                                padding:7px 11px;
                                                border-radius:999px;
                                                background:rgba(255,255,255,.14);
                                                color:#ffffff;
                                                font-size:11px;
                                                font-weight:700;
                                            ">
                                            TICKET READY
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main content -->
                    <tr>
                        <td
                            style="
                                padding:34px 32px 14px;
                            ">
                            <div
                                style="
                                    font-size:25px;
                                    line-height:32px;
                                    font-weight:800;
                                    color:#111827;
                                ">
                                Your ticket is ready
                            </div>

                            <p
                                style="
                                    margin:12px 0 0;
                                    font-size:14px;
                                    line-height:22px;
                                    color:#6b7280;
                                ">
                                Hi
                                <strong style="color:#111827;">
                                    {{ $ticket->attendee_name }}
                                </strong>,
                            </p>

                            <p
                                style="
                                    margin:8px 0 0;
                                    font-size:14px;
                                    line-height:22px;
                                    color:#6b7280;
                                ">
                                Your NaSeRy ticket has been successfully generated.
                                The PDF ticket containing your QR code is attached to this email.
                            </p>
                        </td>
                    </tr>

                    <!-- Event information -->
                    <tr>
                        <td
                            style="
                                padding:12px 32px;
                            ">
                            <div
                                style="
                                    padding:20px;
                                    background:#f8fafc;
                                    border:1px solid #e5e7eb;
                                    border-radius:16px;
                                ">
                                <div
                                    style="
                                        font-size:11px;
                                        font-weight:800;
                                        letter-spacing:.10em;
                                        color:#6b7280;
                                    ">
                                    EVENT
                                </div>

                                <div
                                    style="
                                        margin-top:6px;
                                        font-size:19px;
                                        line-height:26px;
                                        font-weight:800;
                                        color:#111827;
                                    ">
                                    {{ $ticket->event?->name ?? 'NaSeRy Event' }}
                                </div>

                                @if($ticket->event?->event_date)
                                <div
                                    style="
                                            margin-top:12px;
                                            font-size:13px;
                                            line-height:20px;
                                            color:#4b5563;
                                        ">
                                    <strong>
                                        Date:
                                    </strong>

                                    {{ $ticket->event->event_date }}
                                </div>
                                @endif

                                @if($ticket->event?->location)
                                <div
                                    style="
                                            margin-top:5px;
                                            font-size:13px;
                                            line-height:20px;
                                            color:#4b5563;
                                        ">
                                    <strong>
                                        Location:
                                    </strong>

                                    {{ $ticket->event->location }}
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <!-- Ticket details -->
                    <tr>
                        <td
                            style="
                                padding:12px 32px;
                            ">
                            <table
                                role="presentation"
                                width="100%"
                                cellspacing="0"
                                cellpadding="0"
                                border="0"
                                style="
                                    width:100%;
                                    border-collapse:separate;
                                ">
                                <tr>
                                    <td
                                        width="50%"
                                        valign="top"
                                        style="
                                            padding-right:6px;
                                        ">
                                        <div
                                            style="
                                                padding:16px;
                                                background:#eef7f8;
                                                border:1px solid #cfe3e6;
                                                border-radius:14px;
                                            ">
                                            <div
                                                style="
                                                    font-size:10px;
                                                    font-weight:800;
                                                    letter-spacing:.10em;
                                                    color:#64748b;
                                                ">
                                                TICKET TYPE
                                            </div>

                                            <div
                                                style="
                                                    margin-top:5px;
                                                    font-size:15px;
                                                    font-weight:800;
                                                    color:#285F6b;
                                                ">
                                                {{ $ticket->ticketType?->name ?? 'General Admission' }}
                                            </div>
                                        </div>
                                    </td>

                                    <td
                                        width="50%"
                                        valign="top"
                                        style="
                                            padding-left:6px;
                                        ">
                                        <div
                                            style="
                                                padding:16px;
                                                background:#eef7f8;
                                                border:1px solid #cfe3e6;
                                                border-radius:14px;
                                            ">
                                            <div
                                                style="
                                                    font-size:10px;
                                                    font-weight:800;
                                                    letter-spacing:.10em;
                                                    color:#64748b;
                                                ">
                                                TICKET ID
                                            </div>

                                            <div
                                                style="
                                                    margin-top:5px;
                                                    font-size:15px;
                                                    font-weight:800;
                                                    color:#285F6b;
                                                    font-family:'Courier New', Courier, monospace;
                                                ">
                                                TKT-{{ str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT) }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Instructions -->
                    <tr>
                        <td
                            style="
                                padding:12px 32px 32px;
                            ">
                            <div
                                style="
                                    padding:18px;
                                    background:#fffbeb;
                                    border:1px solid #fde68a;
                                    border-radius:14px;
                                ">
                                <div
                                    style="
                                        font-size:14px;
                                        font-weight:800;
                                        color:#92400e;
                                    ">
                                    Important check-in reminder
                                </div>

                                <div
                                    style="
                                        margin-top:6px;
                                        font-size:13px;
                                        line-height:20px;
                                        color:#a16207;
                                    ">
                                    Please present the QR code shown in the attached PDF ticket
                                    when you arrive at the event. Keep the QR code private and do
                                    not share your ticket with other people.
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="
                                padding:20px 32px;
                                background:#f8fafc;
                                border-top:1px solid #e5e7eb;
                            ">
                            <div
                                style="
                                    font-size:12px;
                                    line-height:19px;
                                    color:#94a3b8;
                                    text-align:center;
                                ">
                                This automated message was sent by
                                <strong style="color:#64748b;">
                                    NaSeRy Event Management
                                </strong>.

                                <br>

                                Your PDF ticket is attached to this email.
                            </div>
                        </td>
                    </tr>
                </table>

                <div
                    style="
                        max-width:600px;
                        margin-top:16px;
                        font-size:11px;
                        line-height:17px;
                        color:#9ca3af;
                        text-align:center;
                    ">
                    © {{ date('Y') }} NaSeRy.
                    Secure event registration and ticketing.
                </div>
            </td>
        </tr>
    </table>
</body>

</html>