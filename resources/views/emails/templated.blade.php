<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $template->subject }}</title>
</head>
<body style="margin:0;background:#f5f5f5;color:#2f2f2f;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        {{ $template->previewText }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f5f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e5e5e5;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#171717;color:#ffffff;padding:22px 28px;font-size:20px;font-weight:700;">
                            Unclad Collection
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px;font-size:16px;line-height:1.65;">
                            {!! $template->html !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top:1px solid #eeeeee;padding:18px 28px;color:#737373;font-size:12px;line-height:1.5;">
                            This message was sent by Unclad Collection.
                            @if (! empty($unsubscribeUrl))
                                <br><a href="{{ $unsubscribeUrl }}" style="color:#525252;">Unsubscribe from this optional email category</a>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
