{{ $headline }}
{{ $o->order_code }}

{{ $lead }}
@if ($kind === 'shipped' && $o->tracking_number)

{{ __('Nomor resi') }}: {{ $o->tracking_number }}
@endif

@foreach ($items as $i)
- {{ $i->coffee_name_snapshot }} · {{ $i->level_label }} · {{ $i->grind_label }} · {{ number_format($i->grams) }} g{{ $i->qty > 1 ? ' x '.$i->qty : '' }}: Rp{{ number_format($i->line_total, 0, ',', '.') }}
@endforeach

{{ __('Subtotal') }}: Rp{{ number_format($o->subtotal, 0, ',', '.') }}
{{ __('Ongkir') }}: {{ $o->shipping_cost ? 'Rp'.number_format($o->shipping_cost, 0, ',', '.') : __('Gratis') }}
@if ($o->addon_cost)
{{ __('Tambahan') }} ({{ $o->addonLabel() }}): Rp{{ number_format($o->addon_cost, 0, ',', '.') }}
@endif
@if ($o->discount)
{{ __('Diskon') }}: -Rp{{ number_format($o->discount, 0, ',', '.') }}
@endif
{{ __('Total') }}: Rp{{ number_format($o->total, 0, ',', '.') }}

{{ __('DIKIRIM KE') }}: {{ $o->recipient_name }}, {{ $o->recipient_phone }}
{{ $o->shipping_address }}

{{ $kind === 'placed' && ! $o->isCod() ? __('Bayar pesanan') : __('Lacak pesanan') }}: {{ $url }}
