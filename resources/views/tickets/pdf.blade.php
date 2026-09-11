<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>{{ $ticketId }}</title>

    @php
    $eventName =
    $event?->name
    ?: 'NaSeRy Event';

    $eventType =
    $event?->event_type
    ?: null;

    $location =
    $event?->location
    ?: 'Location not specified';

    $eventDate =
    $event?->event_date
    ? \Illuminate\Support\Carbon::parse(
    $event->event_date
    )->format('F j, Y')
    : 'Date not specified';

    $startTime =
    $event?->start_time
    ? \Illuminate\Support\Carbon::parse(
    $event->start_time
    )->format('g:i A')
    : null;

    $endTime =
    $event?->end_time
    ? \Illuminate\Support\Carbon::parse(
    $event->end_time
    )->format('g:i A')
    : null;

    if ($startTime && $endTime) {
    $eventTime =
    $startTime . ' - ' . $endTime;
    } elseif ($startTime) {
    $eventTime =
    $startTime;
    } else {
    $eventTime =
    'Time not specified';
    }

    $attendeeName =
    $ticket?->attendee_name
    ?: 'Attendee';

    $attendeeEmail =
    $ticket?->attendee_email
    ?: 'Not provided';

    $resolvedTicketType =
    $ticketType
    ?? $ticket?->ticketType;

    $ticketTypeName =
    $resolvedTicketType?->name
    ?: 'General Admission';

    $ticketPrice =
    (float) (
    $resolvedTicketType?->price
    ?? 0
    );

    $ticketStatus =
    $ticket?->checked_in_at
    ? 'Checked-in'
    : 'Registered';

    $hasQr =
    !empty($qrBase64);
    @endphp

    <style>
        @page {
            margin: 14px;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #172033;
            background: #eef4f5;
        }

        .page {
            width: auto;
            min-height: 100%;
            margin: 0;
            padding: 0;
            background: #eef4f5;
        }

        .ticket {
            width: 100%;
            min-height: 800px;
            margin: 0;
            background: #ffffff;
            border: 1px solid #dbe4e6;
            border-radius: 16px;
            overflow: hidden;
            text-align: left;
        }

        .hero {
            width: 100%;
            background: #285f6b;
            color: #ffffff;
            padding: 18px 18px;
        }

        .hero-table,
        .info-table,
        .summary-table,
        .qr-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .hero-table {
            width: 96%;
            margin: 0 auto;
        }

        .hero-table td {
            vertical-align: middle;
        }

        .brand-mark-cell {
            width: 48px;
        }

        .brand-copy-cell {
            width: auto;
        }

        .ticket-code-cell {
            width: 112px;
            padding-right: 2px;
            text-align: right;
            vertical-align: middle;
            overflow: hidden;
        }

        .brand-mark-table {
            width: 38px;
            height: 38px;
            border-collapse: collapse;
        }

        .brand-mark-table td {
            width: 38px;
            height: 38px;
            padding: 0;
            border-radius: 8px;
            background: #ffffff;
            color: #285f6b;
            font-size: 17px;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
        }

        .brand-name {
            font-size: 19px;
            font-weight: 700;
            line-height: 1.1;
            color: #ffffff;
        }

        .brand-subtitle {
            margin-top: 2px;
            font-size: 8px;
            color: #d7e7ea;
        }

        .ticket-code-label {
            text-align: right;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #d7e7ea;
            white-space: nowrap;
        }

        .ticket-code {
            margin-top: 3px;
            text-align: right;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 9.5px;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
        }

        .content {
            padding: 16px 18px 15px;
        }

        .event-pass {
            margin-bottom: 4px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #285f6b;
        }

        .event-title {
            font-size: 22px;
            line-height: 1.2;
            font-weight: 700;
            color: #111827;
        }

        .event-type {
            margin-top: 4px;
            font-size: 10px;
            color: #6b7280;
        }

        .status-row {
            margin-top: 9px;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #e8f5f1;
            color: #18785d;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section {
            margin-top: 13px;
        }

        .section-title {
            margin-bottom: 7px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
        }

        .info-table td {
            vertical-align: top;
            padding: 0 7px 7px 0;
        }

        .info-table td:last-child {
            padding-right: 0;
        }

        .info-card {
            min-height: 50px;
            padding: 9px 10px;
            background: #f8fafb;
            border: 1px solid #e4eaec;
            border-radius: 8px;
        }

        .label {
            margin-bottom: 3px;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
        }

        .value {
            font-size: 10px;
            font-weight: 700;
            color: #172033;
            line-height: 1.35;
            word-wrap: break-word;
        }

        .value-secondary {
            font-size: 9px;
            font-weight: 400;
            color: #526071;
        }

        .summary-table {
            margin-top: 9px;
        }

        .summary-table td {
            width: 33.333%;
            padding: 9px 10px;
            background: #eef7f8;
            border: 1px solid #d8e8eb;
            vertical-align: top;
        }

        .summary-label {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7c82;
        }

        .summary-value {
            margin-top: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #285f6b;
        }

        .qr-panel {
            margin-top: 13px;
            padding: 11px 12px;
            border: 1px solid #dfe7e9;
            border-radius: 12px;
            background: #fbfdfd;
            page-break-inside: avoid;
        }

        .qr-table {
            width: 92%;
            margin: 0 auto;
        }

        .qr-table td {
            vertical-align: middle;
        }

        .qr-cell {
            width: 150px;
            text-align: center;
        }

        .qr-box {
            display: inline-block;
            padding: 7px;
            background: #ffffff;
            border: 1px solid #d8e1e3;
            border-radius: 8px;
        }

        .qr-box img {
            display: block;
            width: 135px;
            height: 135px;
        }

        .qr-copy {
            padding-left: 14px;
            padding-right: 5px;
        }

        .qr-heading {
            font-size: 14px;
            line-height: 1.3;
            font-weight: 700;
            color: #172033;
        }

        .qr-description {
            margin-top: 5px;
            font-size: 9px;
            line-height: 1.5;
            color: #64748b;
        }

        .qr-ticket-id {
            margin-top: 9px;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 10px;
            font-weight: 700;
            color: #285f6b;
            white-space: nowrap;
        }

        .qr-value {
            margin-top: 5px;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 5.5px;
            line-height: 1.35;
            color: #a0aab6;
            word-wrap: break-word;
        }

        .notice {
            width: 96%;
            margin: 12px auto 0;
            padding: 9px 11px;
            border: 1px solid #fde3a7;
            border-radius: 10px;
            background: #fff9eb;
            color: #8a5b0a;
            font-size: 8px;
            line-height: 1.5;
            text-align: center;
            page-break-inside: avoid;
        }

        .notice strong {
            color: #754b00;
        }

        .footer {
            width: 96%;
            margin: 11px auto 0;
            text-align: center;
            font-size: 7px;
            color: #9aa5b1;
        }

        .footer strong {
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="ticket">

            <div class="hero">
                <table class="hero-table">
                    <tr>
                        <td class="brand-mark-cell">
                            <table class="brand-mark-table">
                                <tr>
                                    <td>
                                        N
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <td class="brand-copy-cell">
                            <div class="brand-name">
                                NaSeRy
                            </div>

                            <div class="brand-subtitle">
                                Official Event QR Ticket
                            </div>
                        </td>

                        <td class="ticket-code-cell">
                            <div class="ticket-code-label">
                                Ticket ID
                            </div>

                            <div class="ticket-code">
                                {{ $ticketId }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="content">

                <div class="event-pass">
                    Event Pass
                </div>

                <div class="event-title">
                    {{ $eventName }}
                </div>

                @if($eventType)
                <div class="event-type">
                    {{ $eventType }}
                </div>
                @endif

                <div class="status-row">
                    <span class="status">
                        {{ $ticketStatus }}
                    </span>
                </div>

                <div class="section">
                    <div class="section-title">
                        Event Information
                    </div>

                    <table class="info-table">
                        <tr>
                            <td width="33.333%">
                                <div class="info-card">
                                    <div class="label">
                                        Event Date
                                    </div>

                                    <div class="value">
                                        {{ $eventDate }}
                                    </div>
                                </div>
                            </td>

                            <td width="33.333%">
                                <div class="info-card">
                                    <div class="label">
                                        Time
                                    </div>

                                    <div class="value">
                                        {{ $eventTime }}
                                    </div>
                                </div>
                            </td>

                            <td width="33.333%">
                                <div class="info-card">
                                    <div class="label">
                                        Location
                                    </div>

                                    <div class="value">
                                        {{ $location }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">
                        Attendee Information
                    </div>

                    <table class="info-table">
                        <tr>
                            <td width="50%">
                                <div class="info-card">
                                    <div class="label">
                                        Attendee Name
                                    </div>

                                    <div class="value">
                                        {{ $attendeeName }}
                                    </div>
                                </div>
                            </td>

                            <td width="50%">
                                <div class="info-card">
                                    <div class="label">
                                        Email Address
                                    </div>

                                    <div class="value value-secondary">
                                        {{ $attendeeEmail }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="summary-table">
                    <tr>
                        <td>
                            <div class="summary-label">
                                Ticket Type
                            </div>

                            <div class="summary-value">
                                {{ $ticketTypeName }}
                            </div>
                        </td>

                        <td>
                            <div class="summary-label">
                                Ticket Price
                            </div>

                            <div class="summary-value">
                                PHP {{ number_format(
                                    $ticketPrice,
                                    2
                                ) }}
                            </div>
                        </td>

                        <td>
                            <div class="summary-label">
                                Ticket Status
                            </div>

                            <div class="summary-value">
                                {{ $ticketStatus }}
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="qr-panel">
                    <table class="qr-table">
                        <tr>
                            <td class="qr-cell">
                                @if($hasQr)
                                <div class="qr-box">
                                    <img
                                        src="data:image/png;base64,{{ $qrBase64 }}"
                                        alt="NaSeRy Ticket QR Code">
                                </div>
                                @else
                                <div class="info-card">
                                    QR code unavailable
                                </div>
                                @endif
                            </td>

                            <td class="qr-copy">
                                <div class="qr-heading">
                                    Ready for event check-in
                                </div>

                                <div class="qr-description">
                                    Present this QR code to the event organizer.
                                    The code will be scanned to verify your ticket
                                    and complete your check-in.
                                </div>

                                <div class="qr-ticket-id">
                                    {{ $ticketId }}
                                </div>

                                @if(!empty($qrValue))
                                <div class="qr-value">
                                    {{ $qrValue }}
                                </div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="notice">
                    <strong>
                        Keep your ticket private.
                    </strong>

                    This QR code is unique to {{ $attendeeName }}.
                    Do not share, duplicate, or publicly post it.
                    Only one successful check-in is allowed.
                </div>

                <div class="footer">
                    Generated by
                    <strong>
                        NaSeRy Event Management
                    </strong>
                    &nbsp;•&nbsp;
                    {{ $ticketId }}
                </div>
            </div>
        </div>
    </div>
</body>

</html>