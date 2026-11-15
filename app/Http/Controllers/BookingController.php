<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingRequest;
use App\Modules\Booking\BookingService;
use App\Modules\Booking\Exceptions\BookingConflictException;
use App\Modules\Booking\Exceptions\BookingNotCancellableException;
use App\Modules\Booking\Exceptions\ItemInactiveException;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\ItemCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly BookingService $bookings,
        private readonly ItemCatalogService $catalog,
    ) {
    }

    /**
     * Request Booking: the form, plus the booked ranges for the chosen item and date.
     */
    public function create(Request $request): View
    {
        $item = $request->query('item');
        $date = $request->query('date');

        $hasSelection = is_numeric($item) && is_string($date) && $date !== '';

        return view('bookings.create', [
            'items' => $this->catalog->listActive(),
            'selectedItem' => $hasSelection ? (int) $item : null,
            'selectedDate' => $hasSelection ? $date : null,
            'ranges' => $hasSelection ? $this->bookings->bookedRangesFor((int) $item, $date) : [],
        ]);
    }

    /**
     * Create the booking request for the logged-in member.
     */
    public function store(BookingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->bookings->createRequest(
                (int) $request->user()->id,
                (int) $data['item_id'],
                (string) $data['date'],
                (string) $data['start_time'],
                (string) $data['end_time'],
            );
        } catch (BookingConflictException) {
            return back()->withInput()->withErrors([
                'start_time' => 'This time overlaps an existing booking.',
            ]);
        } catch (ItemInactiveException) {
            return back()->withInput()->withErrors([
                'item_id' => 'This item is deactivated and cannot be booked.',
            ]);
        }

        return redirect('/bookings')->with('status', 'Booking requested (Pending).');
    }

    /**
     * My Bookings: the logged-in member's own bookings, newest date first (FR-10).
     */
    public function index(Request $request): View
    {
        return view('bookings.index', [
            'bookings' => $this->bookings->listForMember((int) $request->user()->id),
        ]);
    }

    /**
     * Cancel an own booking; the policy denies anyone else with 403 (FR-08, NFR-02).
     */
    public function cancel(Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);

        try {
            $this->bookings->cancel((int) $booking->id);
        } catch (BookingNotCancellableException) {
            return back()->with('error', 'This booking can no longer be cancelled.');
        }

        return redirect('/bookings')->with('status', 'Booking cancelled.');
    }
}
