<?php

namespace App\Http\Controllers;

use App\Modules\Booking\BookingService;
use App\Modules\Booking\Exceptions\BookingAlreadyDecidedException;
use App\Modules\Booking\Exceptions\ItemInactiveException;
use App\Modules\Booking\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    /**
     * Supervisor Dashboard: pending requests, the chosen day's schedule and
     * each active item's booked vs free ranges (FR-11).
     */
    public function index(Request $request): View
    {
        $date = $this->date($request);

        return view('dashboard.index', [
            'date' => $date,
            'schedule' => $this->bookings->supervisorSchedule($date),
        ]);
    }

    /**
     * Approve or reject a pending booking, then return to the same day (FR-06, FR-07, FR-14).
     */
    public function decide(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = (string) ($data['date'] ?? now()->toDateString());
        $dashboard = '/dashboard?date='.$date;

        try {
            if ($data['decision'] === 'approve') {
                $this->bookings->approve((int) $booking->id);
            } else {
                $this->bookings->reject((int) $booking->id);
            }
        } catch (BookingAlreadyDecidedException) {
            return redirect($dashboard)->with('error', 'This booking was already handled.');
        } catch (ItemInactiveException) {
            return redirect($dashboard)->with('error', 'This item is deactivated and cannot be booked');
        }

        return redirect($dashboard)->with(
            'status',
            $data['decision'] === 'approve' ? 'Booking approved.' : 'Booking rejected.',
        );
    }

    /**
     * The selected day, today when the query string carries no valid date.
     */
    private function date(Request $request): string
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return (string) ($validated['date'] ?? now()->toDateString());
    }
}
