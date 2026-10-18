@php
    $rp = fn ($n) => 'Rp'.number_format($n, 0, ',', '.');
    $blue = '#2A5DB0'; $ink = '#1A1814'; $muted = '#6B645A'; $line = '#E4DCCB'; $bg = '#F5F0E6';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>{{ $headline }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $bg }};font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:{{ $ink }};">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $lead }}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $bg }};">
<tr><td align="center" style="padding:28px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

    <tr><td style="padding:0 4px 16px;font-size:15px;font-weight:700;letter-spacing:0.22em;color:{{ $ink }};">{{ mb_strtoupper($store) }}</td></tr>

    <tr><td style="background:#FFFFFF;border:1px solid {{ $line }};border-radius:8px;padding:28px 26px;">

        <p style="margin:0;font-size:12px;letter-spacing:0.14em;color:{{ $muted }};font-family:Consolas,Menlo,monospace;">{{ $o->order_code }}</p>
        <h1 style="margin:8px 0 0;font-size:24px;line-height:1.2;color:{{ $ink }};">{{ $headline }}</h1>
        <p style="margin:14px 0 0;font-size:15px;line-height:1.6;color:{{ $ink }};">{{ $lead }}</p>

        @if ($kind === 'shipped' && $o->tracking_number)
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;background:{{ $bg }};border-radius:6px;">
                <tr><td style="padding:14px 16px;">
                    <p style="margin:0;font-size:12px;color:{{ $muted }};">{{ __('Nomor resi') }}</p>
                    <p style="margin:4px 0 0;font-size:18px;font-weight:700;font-family:Consolas,Menlo,monospace;color:{{ $ink }};">{{ $o->tracking_number }}</p>
                </td></tr>
            </table>
        @endif

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px;"><tr><td align="left">
            <a href="{{ $url }}" style="display:inline-block;background:{{ $blue }};color:#FFFFFF;text-decoration:none;font-weight:700;font-size:15px;padding:13px 24px;border-radius:6px;">
                {{ $kind === 'placed' && ! $o->isCod() ? __('Bayar pesanan') : __('Lacak pesanan') }}
            </a>
        </td></tr></table>

        <p style="margin:30px 0 8px;font-size:12px;letter-spacing:0.14em;color:{{ $muted }};font-family:Consolas,Menlo,monospace;">{{ __('RINCIAN PESANAN') }}</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $line }};">
            @foreach ($items as $i)
                <tr>
                    <td style="padding:12px 0;border-bottom:1px solid {{ $line }};font-size:14px;line-height:1.45;">
                        <strong>{{ $i->coffee_name_snapshot }}</strong><br>
                        <span style="color:{{ $muted }};font-size:13px;">{{ $i->descr }}{{ $i->qty > 1 ? ($i->descr ? ' × ' : '× ').$i->qty : '' }}</span>
                    </td>
                    <td align="right" valign="top" style="padding:12px 0;border-bottom:1px solid {{ $line }};font-size:14px;white-space:nowrap;font-family:Consolas,Menlo,monospace;">{{ $rp($i->line_total) }}</td>
                </tr>
            @endforeach
            <tr><td style="padding:12px 0 0;font-size:14px;color:{{ $muted }};">{{ __('Subtotal') }}</td><td align="right" style="padding:12px 0 0;font-size:14px;font-family:Consolas,Menlo,monospace;">{{ $rp($o->subtotal) }}</td></tr>
            <tr><td style="padding:6px 0 0;font-size:14px;color:{{ $muted }};">{{ __('Ongkir') }}</td><td align="right" style="padding:6px 0 0;font-size:14px;font-family:Consolas,Menlo,monospace;">{{ $o->shipping_cost ? $rp($o->shipping_cost) : __('Gratis') }}</td></tr>
            @if ($o->addon_cost)
                <tr><td style="padding:6px 0 0;font-size:14px;color:{{ $muted }};">{{ __('Tambahan') }} ({{ $o->addonLabel() }})</td><td align="right" style="padding:6px 0 0;font-size:14px;font-family:Consolas,Menlo,monospace;">{{ $rp($o->addon_cost) }}</td></tr>
            @endif
            @if ($o->discount)
                <tr><td style="padding:6px 0 0;font-size:14px;color:{{ $muted }};">{{ __('Diskon') }}{{ $o->voucher_code ? ' ('.$o->voucher_code.')' : '' }}</td><td align="right" style="padding:6px 0 0;font-size:14px;font-family:Consolas,Menlo,monospace;">-{{ $rp($o->discount) }}</td></tr>
            @endif
            <tr><td style="padding:12px 0 0;font-size:16px;font-weight:700;">{{ __('Total') }}</td><td align="right" style="padding:12px 0 0;font-size:16px;font-weight:700;font-family:Consolas,Menlo,monospace;">{{ $rp($o->total) }}</td></tr>
        </table>

        <p style="margin:30px 0 8px;font-size:12px;letter-spacing:0.14em;color:{{ $muted }};font-family:Consolas,Menlo,monospace;">{{ __('DIKIRIM KE') }}</p>
        <p style="margin:0;font-size:14px;line-height:1.55;">
            <strong>{{ $o->recipient_name }}</strong> · {{ $o->recipient_phone }}<br>
            <span style="color:{{ $muted }};">{!! nl2br(e($o->shipping_address)) !!}</span>
        </p>
        <p style="margin:14px 0 0;font-size:13px;color:{{ $muted }};">{{ __('Pembayaran') }}: {{ $o->payment_label }}</p>
    </td></tr>

    <tr><td style="padding:18px 6px 0;font-size:12px;line-height:1.6;color:{{ $muted }};">
        {{ __('Email ini dikirim otomatis karena kamu memesan di :store.', ['store' => $store]) }}
        @if ($contact) {{ __('Ada pertanyaan? Balas email ini atau tulis ke :mail.', ['mail' => $contact]) }} @endif
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
