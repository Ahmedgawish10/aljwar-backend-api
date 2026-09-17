<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Customer Inquiry</title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #f4f6f8;
    font-family: Arial, Helvetica, sans-serif;
    color: #1f2937;
">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f6f8; padding: 40px 20px;">
    <tr>
        <td align="center">

            <!-- Main Container -->
            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                border="0"
                style="
                    max-width: 650px;
                    background-color: #ffffff;
                    border-radius: 10px;
                    overflow: hidden;
                    border: 1px solid #e5e7eb;
                "
            >

                <!-- Header -->
                <tr>
                    <td style="
                        padding: 28px 32px;
                        background-color: #111827;
                        color: #ffffff;
                    ">
                        <h1 style="
                            margin: 0;
                            font-size: 22px;
                            font-weight: 600;
                        ">
                            New Customer Inquiry
                        </h1>

                        <p style="
                            margin: 8px 0 0;
                            font-size: 14px;
                            color: #d1d5db;
                        ">
                            You have received a new inquiry from the Al Gewar website.
                        </p>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding: 32px;">

                        <!-- Contact Information -->
                        <h2 style="
                            margin: 0 0 18px;
                            font-size: 17px;
                            color: #111827;
                        ">
                            Contact Information
                        </h2>

                        <table width="100%" cellpadding="0" cellspacing="0" border="0">

                            <tr>
                                <td style="
                                    padding: 10px 0;
                                    width: 140px;
                                    color: #6b7280;
                                    font-size: 14px;
                                ">
                                    Name
                                </td>

                                <td style="
                                    padding: 10px 0;
                                    font-size: 14px;
                                    font-weight: 500;
                                ">
                                    {{ $contactMessage->first_name }}
                                    {{ $contactMessage->last_name }}
                                </td>
                            </tr>

                            <tr>
                                <td style="
                                    padding: 10px 0;
                                    color: #6b7280;
                                    font-size: 14px;
                                ">
                                    Email
                                </td>

                                <td style="
                                    padding: 10px 0;
                                    font-size: 14px;
                                ">
                                    <a
                                        href="mailto:{{ $contactMessage->email }}"
                                        style="
                                            color: #2563eb;
                                            text-decoration: none;
                                        "
                                    >
                                        {{ $contactMessage->email }}
                                    </a>
                                </td>
                            </tr>

                            <tr>
                                <td style="
                                    padding: 10px 0;
                                    color: #6b7280;
                                    font-size: 14px;
                                ">
                                    Phone
                                </td>

                                <td style="
                                    padding: 10px 0;
                                    font-size: 14px;
                                ">
                                    {{ $contactMessage->phone ?: 'N/A' }}
                                </td>
                            </tr>

                            <tr>
                                <td style="
                                    padding: 10px 0;
                                    color: #6b7280;
                                    font-size: 14px;
                                ">
                                    Country
                                </td>

                                <td style="
                                    padding: 10px 0;
                                    font-size: 14px;
                                ">
                                    {{ $contactMessage->country ?: 'N/A' }}
                                </td>
                            </tr>

                            <tr>
                                <td style="
                                    padding: 10px 0;
                                    color: #6b7280;
                                    font-size: 14px;
                                ">
                                    Service
                                </td>

                                <td style="
                                    padding: 10px 0;
                                    font-size: 14px;
                                ">
                                    {{ $contactMessage->service ?: 'N/A' }}
                                </td>
                            </tr>

                            <tr>
                                <td style="
                                    padding: 10px 0;
                                    color: #6b7280;
                                    font-size: 14px;
                                ">
                                    Subject
                                </td>

                                <td style="
                                    padding: 10px 0;
                                    font-size: 14px;
                                ">
                                    {{ $contactMessage->subject ?: 'N/A' }}
                                </td>
                            </tr>

                        </table>

                        <!-- Divider -->
                        <div style="
                            height: 1px;
                            background-color: #e5e7eb;
                            margin: 28px 0;
                        "></div>

                        <!-- Message -->
                        <h2 style="
                            margin: 0 0 14px;
                            font-size: 17px;
                            color: #111827;
                        ">
                            Message
                        </h2>

                        <div style="
                            padding: 18px;
                            background-color: #f9fafb;
                            border: 1px solid #e5e7eb;
                            border-radius: 6px;
                            font-size: 14px;
                            line-height: 1.7;
                            color: #374151;
                        ">
                            {!! nl2br(e($contactMessage->message)) !!}
                        </div>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="
                        padding: 20px 32px;
                        background-color: #f9fafb;
                        border-top: 1px solid #e5e7eb;
                        text-align: center;
                    ">
                        <p style="
                            margin: 0;
                            font-size: 12px;
                            color: #9ca3af;
                        ">
                            Received via the Al Gewar website.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>