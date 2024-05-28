<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Lecturer extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'password', 'faculty_id', 'department_id'];

    protected $hidden = ['password']; // Add this line to hide the password attribute

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get the password for the admin model.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }
}
