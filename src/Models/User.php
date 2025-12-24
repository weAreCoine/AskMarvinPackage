<?php

declare(strict_types=1);

namespace Marvin\Ask\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Marvin\Ask\Enums\Role;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    public bool $isAdministrator {
        get => $this->role === Role::Admin;
    }

    public bool $isManager {
        get => $this->role === Role::Manager;
    }

    public bool $isUser {
        get => $this->role === Role::User;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $protected = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // TODO Change the logic if not every user can access every panel
        return true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }
}
