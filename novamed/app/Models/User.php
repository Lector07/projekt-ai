<?php

namespace App\Models;

use App\Notifications\EmailVerification;
use App\Notifications\PasswordReset;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

class User extends Authenticatable implements CanResetPassword, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, CanResetPasswordTrait;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'profile_picture_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['profile_picture_url'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function appointmentsAsPatient(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class, 'user_id', 'id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    public function hasRole(string $roleSlug): bool
    {
        return $this->role === $roleSlug;
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        if ($this->profile_picture_path) {
            return Storage::disk('public')->url($this->profile_picture_path);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=random&color=fff';
    }


    public function sendEmailVerificationNotification()
    {
        $this->notify(new EmailVerification());
    }


    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordReset($token));
    }
}
