<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $order->order_code }} - KROMATIK</title>
    <style>
        * { box-sizing: border-box; }
        body { font: 14px/1.5 'IBM Plex Sans', system-ui, sans-serif; color: #111; margin: 0; background: #eee; }
        .sheet { width: 780px; max-width: 100%; margin: 24px auto; background: #fff; padding: 36px 40px; }
        h1 { font-size: 20px; letter-spacing: .2em; margin: 0; }
        .row { display: flex; justify-content: space-between; gap: 24px; }
        .muted { color: #666; font-size: 12.5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #ddd; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #666; }
        td.r, th.r { text-align: right; }
        .label { border: 2px dashed #111; padding: 16px 18px; margin-top: 26px; }
        .label b { font-size: 16px; }
        .bar { text-align: center; padding: 12px; }
        button { font: inherit; padding: 8px 18px; cursor: pointer; }
        @media print { body { background: #fff; } .sheet { margin: 0; width: auto; padding: 0; } .bar { display: none; } }
    </style>
</head>
<body>
<div class="bar"><button onclick="window.print()">{{ __('Cetak') }}</button></div>
<div class="sheet">
    <div class="row">
        <div><h1>{{ \App\Models\Setting::get('store_name') }}</h1><div class="muted">{{ \App\Models\Setting::get('store_tagline') }} · {{ \App\Models\Setting::get('store_city') }}</div></div>
        <div style="text-align:right"><b>{{ __('Invoice') }}</b><br>{{ $order->order_code }}<div class="muted">{{ $order->created_at->translatedFormat('j F Y, H:i') }}</div></div>
    </div>

    <div class="row" style="margin-top:22px">
        <div><div class="muted">{{ __('Ditagihkan ke') }}</div><b>{{ $order->user?->name ?? $order->recipient_name }}</b><br>{{ $order->user?->email }}</div>
        <div style="text-align:right"><div class="muted">{{ __('Status') }}</div><b>{{ $order->status_label }}</b>@if ($order->paid_at)<div class="muted">{{ __('Dibayar') }} {{ $order->paid_at->translatedFormat('j M Y, H:i') }}</div>@endif</div>
    </div>

    <table>
        <thead><tr><th>{{ __('Item') }}</th><th>{{ __('Level') }}</th><th>{{ __('Gilingan') }}</th><th class="r">{{ __('Berat') }}</th><th class="r">{{ __('Harga') }}</th></tr></thead>
        <tbody>
        @foreach ($order->groupedItems() as $i)
            <tr><td>{{ $i->coffee_name_snapshot }}</td><td>{{ $i->level_label }}</td><td>{{ __($i->grind_label) }}</td><td class="r">{{ number_format($i->total_grams, 0, ',', '.') }} g</td><td class="r">Rp {{ number_format($i->line_total, 0, ',', '.') }}</td></tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="r muted">{{ __('Total berat') }}</td><td class="r">{{ number_format($order->items->sum('grams'), 0, ',', '.') }} g</td><td></td></tr>
            <tr><td colspan="4" class="r muted">{{ __('Subtotal') }}</td><td class="r">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
            @if ($order->discount ?? 0)<tr><td colspan="4" class="r muted">{{ __('Diskon') }}@if ($order->voucher_code) ({{ $order->voucher_code }})@endif</td><td class="r">- Rp {{ number_format($order->discount, 0, ',', '.') }}</td></tr>@endif
            <tr><td colspan="4" class="r muted">{{ __('Ongkir') }}</td><td class="r">{{ $order->shipping_cost ? 'Rp ' . number_format($order->shipping_cost, 0, ',', '.') : __('Gratis') }}</td></tr>
            <tr><td colspan="4" class="r"><b>{{ __('Total') }}</b></td><td class="r"><b>Rp {{ number_format($order->total, 0, ',', '.') }}</b></td></tr>
        </tfoot>
    </table>

    <div class="label">
        <div class="muted">{{ __('Kirim ke') }}</div>
        <b>{{ $order->recipient_name }}</b> · {{ $order->recipient_phone }}<br>
        {{ $order->shipping_address }}
        @if ($order->tracking_number)<div class="muted" style="margin-top:8px">{{ __('Resi') }}: <b style="color:#111">{{ $order->tracking_number }}</b></div>@endif
    </div>
    <p class="muted" style="margin-top:22px">{{ __('Disangrai setelah pesanan masuk. Terima kasih sudah memilih KROMATIK.') }}</p>
</div>
</body>
</html>
