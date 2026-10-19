@props(['title' => 'Your NP Digital account'])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ $title }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
@media only screen and (max-width: 620px) {
.content {
width: 100% !important;
}
.content-cell, .header, .footer-cell {
padding: 28px 24px !important;
}
.feature {
display: block !important;
width: 100% !important;
padding: 0 0 18px !important;
}
.button {
display: block !important;
width: 100% !important;
text-align: center !important;
}
}
</style>
{{ $head ?? '' }}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center" class="outer-cell">
<table class="content" width="600" cellpadding="0" cellspacing="0" role="presentation">
{{ $header ?? '' }}

<tr>
<td class="content-cell">
{{ Illuminate\Mail\Markdown::parse($slot) }}

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:40px;border-top:1px solid #e2e9f1">
<tr><td style="padding-top:26px">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr>
<td class="feature" width="33%" valign="top" style="padding-right:12px"><div style="font-size:24px;color:#12a46b">&#9632;</div><strong style="font-size:14px;line-height:22px;color:#102340">Track performance</strong><p style="font-size:13px;line-height:21px;color:#65758d;margin:7px 0">See your results in real time.</p></td>
<td class="feature" width="33%" valign="top" style="padding-right:12px"><div style="font-size:24px;color:#0879ed">&#9678;</div><strong style="font-size:14px;line-height:22px;color:#102340">Expert resources</strong><p style="font-size:13px;line-height:21px;color:#65758d;margin:7px 0">Find insights and guidance.</p></td>
<td class="feature" width="34%" valign="top"><div style="font-size:24px;color:#334cb4">&#9679;</div><strong style="font-size:14px;line-height:22px;color:#102340">Dedicated support</strong><p style="font-size:13px;line-height:21px;color:#65758d;margin:7px 0">We're here to help.</p></td>
</tr>
</table>
</td></tr>
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:25px;background:#f0f5fa;border-radius:9px">
<tr><td style="padding:22px"><strong style="font-size:15px;color:#102340">Keep your account secure</strong><p style="font-size:14px;line-height:23px;color:#53647e;margin:8px 0 0">If you didn't request this activity or need help, contact <a href="mailto:{{ config('mail.support_address') }}">{{ config('mail.support_address') }}</a>.</p></td></tr>
</table>

{{ $subcopy ?? '' }}
</td>
</tr>

{{ $footer ?? '' }}
</table>
</td>
</tr>
</table>
</body>
</html>
