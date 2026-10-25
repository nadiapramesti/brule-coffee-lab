<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coffee;
use App\Models\Notice;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;

/** Notifikasi dalam aplikasi: daftar, jumlah belum dibaca, tandai dibaca. Staf juga mendapat peringatan pesanan telat. */
class NoticeController extends Controller
{
    private const LATE_HOURS = 24;

    /** Buat notifikasi "pesanan > 24 jam" sekali per pesanan (tanpa scheduler: dipicu saat staf membuka/polling). */
    private function syncLate(Request $request): void
    {
        if (! $request->user()->isStaff()) {
            return;
        }

        $late = Order::whereIn('status', StaffController::COLUMNS)
            ->whereRaw('COALESCE(paid_at, created_at) < ?', [now()->subHours(self::LATE_HOURS)])
            ->limit(50)->get(['id', 'order_code', 'recipient_name', 'status']);

        if ($late->isEmpty()) {
            return;
        }

        $already = Notice::where('type', 'late')
            ->where(function ($q) use ($late) {
                foreach ($late as $o) {
                    $q->orWhere('params', 'like', '%"code":"' . $o->order_code . '"%');
                }
            })
            ->get()->map(fn ($n) => $n->params['code'] ?? null)->filter()->all();

        foreach ($late as $o) {
            if (in_array($o->order_code, $already, true)) {
                continue;
            }
            Notice::send(Notice::staff(), 'late', 'Pesanan lebih dari 24 jam', ':code · :n belum berangkat', [
                'code' => $o->order_code, 'n' => $o->recipient_name,
            ]);
        }
    }

    public function unread(Request $request)
    {
        $this->syncLate($request);

        return ['unread' => Notice::where('user_id', $request->user()->id)->unread()->count()];
    }

    public function index(Request $request)
    {
        $this->syncLate($request);
        $uid = $request->user()->id;

        $page = Notice::where('user_id', $uid)->latest('id')->paginate(20);

        $alerts = [];
        if ($request->user()->isStaff()) {
            $late = Order::whereIn('status', StaffController::COLUMNS)
                ->whereRaw('COALESCE(paid_at, created_at) < ?', [now()->subHours(self::LATE_HOURS)])->count();
            $low = Coffee::active()->where('stock_green_bean_gram', '<', Setting::int('low_stock_gram'))->count();
            $waiting = Order::where('status', 'queued')->count();

            if ($late > 0) {
                $alerts[] = ['type' => 'late', 'text' => "$late pesanan lebih dari 24 jam belum berangkat", 'target' => 'queue'];
            }
            if ($low > 0) {
                $alerts[] = ['type' => 'stock', 'text' => "$low biji stoknya menipis atau habis", 'target' => 'stock'];
            }
            if ($waiting > 0) {
                $alerts[] = ['type' => 'queue', 'text' => "$waiting pesanan menunggu disangrai", 'target' => 'queue'];
            }
        }

        return [
            'unread' => Notice::where('user_id', $uid)->unread()->count(),
            'alerts' => $alerts,
            'data' => $page->getCollection()->map(fn (Notice $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->titleText(),
                'body' => $n->bodyText(),
                'code' => $n->params['code'] ?? null,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
            ])->values(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ];
    }

    public function read(Request $request, int $id)
    {
        Notice::where('user_id', $request->user()->id)->where('id', $id)->whereNull('read_at')->update(['read_at' => now()]);

        return ['ok' => true];
    }

    public function readAll(Request $request)
    {
        Notice::where('user_id', $request->user()->id)->unread()->update(['read_at' => now()]);

        return ['ok' => true];
    }
}
