<?php

namespace App\Events;

use App\Models\BusLocation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Pushed to admin and supervisor live-tracking screens each time a driver's
 * browser reports a new GPS fix. Carries everything a map marker needs so the
 * client never has to make a follow-up request.
 */
class BusLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public BusLocation $location) {}

    /**
     * Broadcast on a single public channel — anyone already permitted onto a
     * live-tracking page may watch every active bus.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('live-tracking');
    }

    /**
     * Broadcast under the short class name so the client listens for
     * `.BusLocationUpdated` without the namespace.
     */
    public function broadcastAs(): string
    {
        return 'BusLocationUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $schedule = $this->location->scheduleRun?->schedule;

        return [
            'bus_id' => $this->location->bus_id,
            'bus_registration' => $this->location->bus?->registration_number,
            'driver_name' => $this->location->driver?->name,
            'route_name' => $schedule?->route?->name,
            'latitude' => (float) $this->location->latitude,
            'longitude' => (float) $this->location->longitude,
            'departure_time' => substr((string) $schedule?->departure_time, 0, 5),
            'arrival_time' => substr((string) $schedule?->arrival_time, 0, 5),
            'recorded_at' => $this->location->recorded_at?->toIso8601String(),
        ];
    }
}
