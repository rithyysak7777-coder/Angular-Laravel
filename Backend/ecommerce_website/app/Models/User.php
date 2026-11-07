<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'fullName',
        'email',
        'password_hash',
        'phone_number',
        'is_active',
    ];

    /**
     * Appends to JSON serialization.
     *
     * @var list<string>
     */
    protected $appends = [
        'name',
        'full_name',
        'first_name',
        'last_name',
        'avatar',
        'role_name',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the name of the password attribute for authentication.
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Mutator/Accessor to seamlessly support $user->password assignment.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => [
                'password_hash' => Hash::needsRehash($value) ? Hash::make($value) : $value,
            ]
        );
    }

    /**
     * Accessor for fullName attribute.
     */
    public function getFullNameAttribute(): string
    {
        return $this->attributes['fullName'] ?? '';
    }

    /**
     * Mutator for fullName attribute.
     */
    public function setFullNameAttribute($value): void
    {
        $this->attributes['fullName'] = trim((string) $value);
    }

    /**
     * Accessor for name alias.
     */
    public function getNameAttribute(): string
    {
        return $this->attributes['fullName'] ?? '';
    }

    /**
     * Mutator for name alias.
     */
    public function setNameAttribute($value): void
    {
        $this->attributes['fullName'] = trim((string) $value);
    }

    /**
     * Accessor for user avatar.
     */
    public function getAvatarAttribute(): string
    {
        if (!empty($this->attributes['avatar'])) {
            return $this->attributes['avatar'];
        }
        $displayName = $this->attributes['fullName'] ?? 'Customer';
        $encoded = urlencode(trim((string) $displayName) ?: 'Customer');
        return "https://ui-avatars.com/api/?name={$encoded}&background=171717&color=fff&size=128&bold=true";
    }

    /**
     * Accessor for role_name string ('admin' vs 'customer').
     */
    public function getRoleNameAttribute(): string
    {
        $role = $this->relationLoaded('role') ? $this->getRelation('role') : $this->role()->first();
        return strtolower($role?->name ?? ($this->role_id === 1 ? 'admin' : 'customer'));
    }

    /**
     * Accessor for is_admin boolean.
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->role_name === 'admin' || $this->role_id === 1;
    }

    /**
     * Compatibility accessor for first_name.
     */
    public function getFirstNameAttribute(): string
    {
        $fullName = $this->attributes['fullName'] ?? '';
        $parts = explode(' ', $fullName, 2);
        return $parts[0] ?? '';
    }

    /**
     * Compatibility accessor for last_name.
     */
    public function getLastNameAttribute(): string
    {
        $fullName = $this->attributes['fullName'] ?? '';
        $parts = explode(' ', $fullName, 2);
        return $parts[1] ?? '';
    }

    /**
     * Compatibility mutator for first_name.
     */
    public function setFirstNameAttribute($value): void
    {
        $last = $this->lastName;
        $this->attributes['fullName'] = trim("{$value} {$last}");
    }

    /**
     * Compatibility mutator for last_name.
     */
    public function setLastNameAttribute($value): void
    {
        $first = $this->firstName;
        $this->attributes['fullName'] = trim("{$first} {$value}");
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'is_active'     => 'boolean',
            'created_at'    => 'datetime:Y-m-d H:i:s',
            'updated_at'    => 'datetime:Y-m-d H:i:s',
        ];
    }

    /**
     * Prepare a date for array / JSON serialization in real local time.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->setTimezone(new \DateTimeZone(config('app.timezone', 'Asia/Phnom_Penh')))->format('Y-m-d H:i:s');
    }

    /**
     * The role that this user belongs to.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * The orders placed by this user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * Active cart items belonging to this user.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'user_id');
    }
}
