<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PermissionChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $userId;
    public array $permisos;

    public function __construct(User $user)
    {
        $this->userId = $user->id;
        $this->permisos = $user->permisosFinales()
            ->pluck('slug')
            ->values()
            ->toArray();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'permission.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'permisos' => $this->permisos,
            'message' => 'Tus permisos fueron actualizados.',
        ];
    }
}