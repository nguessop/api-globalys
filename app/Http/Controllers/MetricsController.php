<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use OpenApi\Annotations as OA;

class MetricsController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/metrics/overview",
     *   tags={"Metrics"},
     *   summary="KPIs pour l'accueil (prestataires, réservations, satisfaction)",
     *   description="KPIs dynamiques avec valeurs brutes et formatées. Périodes: all (défaut), today, 7d, 30d, 90d, ytd, mtd.",
     *   @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"all","today","7d","30d","90d","mtd","ytd"})),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function overview(Request $request)
    {
        // Période
        [$from, $to, $period] = $this->resolvePeriod($request->string('period')->toString());

        // Cache 5 min pour éviter des COUNT lourds
        $cacheKey = sprintf('metrics:overview:%s:%s', $period, now()->format('YmdHi'));
        $payload = Cache::remember($cacheKey, 300, function () use ($from, $to, $period) {
            // Prestataires (Users.user_type = 'prestataire')
            $providersCount = User::query()
                ->where('user_type', 'prestataire')
                ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
                ->count();

            // Réservations (total)
            $bookingsCount = Booking::query()
                ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
                ->count();

            // Satisfaction = % d’avis approuvés avec rating >= 4
            $approved = Review::query()
                ->where('is_approved', true)
                ->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]));

            $approvedCount = (clone $approved)->count();
            $positiveCount = (clone $approved)->where('rating', '>=', 4)->count();
            $avgRating     = $approvedCount > 0 ? round((float) $approved->avg('rating'), 2) : null;
            $satisfaction  = $approvedCount > 0 ? (int) round($positiveCount * 100 / $approvedCount) : null;

            return [
                'period' => $period,
                'from'   => $from?->toIso8601String(),
                'to'     => $to?->toIso8601String(),
                'raw' => [
                    'providers_total'        => $providersCount,
                    'bookings_total'         => $bookingsCount,
                    'reviews_total'          => $approvedCount,
                    'avg_rating'             => $avgRating,    // ex: 4.85
                    'satisfaction_percent'   => $satisfaction, // ex: 99
                ],
                'display' => [
                    'providers'    => $this->abbrPlus($providersCount),       // ex: "10k+"
                    'bookings'     => $this->abbrPlus($bookingsCount),        // ex: "50k+"
                    'satisfaction' => $satisfaction !== null ? $satisfaction.'%' : '—',
                ],
            ];
        });

        // Utilise ton helper global; sinon remplace par response()->json($payload)
        return response()->success($payload, 'KPIs');
    }

    /** Convertit 0..∞ vers "10k+" / "50k+" / "999+" selon seuils */
    private function abbrPlus(int $n): string
    {
        if ($n >= 1_000_000) return floor($n / 1_000_000) . 'M+';
        if ($n >= 100_000)   return floor($n / 1_000) . 'k+';
        if ($n >= 10_000)    return floor($n / 1_000) . 'k+';
        if ($n >= 1_000)     return number_format($n); // "1,234"
        return (string) $n;
    }

    /** Résout la période en (from, to, label) */
    private function resolvePeriod(?string $period): array
    {
        $period = $period ?: 'all';
        $now = now();

        return match ($period) {
            'today' => [ $now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today' ],
            '7d'    => [ $now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), '7d' ],
            '30d'   => [ $now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), '30d' ],
            '90d'   => [ $now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay(), '90d' ],
            'mtd'   => [ $now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'mtd' ],
            'ytd'   => [ $now->copy()->startOfYear(), $now->copy()->endOfDay(), 'ytd' ],
            default => [ null, null, 'all' ],
        };
    }
}
