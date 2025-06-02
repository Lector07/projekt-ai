<?php

namespace App\Observers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DoctorObserver
{
    /**
     * Handle the Doctor "deleted" event.
     *
     * This method will be called AFTER the doctor record has been deleted from the database.
     *
     * @param  \App\Models\Doctor  $doctor
     * @return void
     */
    public function deleted(Doctor $doctor): void
    {
        if ($doctor->user_id) {
            $user = User::find($doctor->user_id);

            if ($user) {
                if ($user->role === 'doctor') {
                    $otherDoctorProfilesCount = Doctor::where('user_id', $user->id)->count();

                    if ($otherDoctorProfilesCount === 0) {
                        $user->role = 'patient';
                        $user->save();

                    } else {
                        Log::info("DoctorObserver: User ID: {$user->id} still has other doctor profiles. Role not changed.");
                    }
                } else {
                    Log::info("DoctorObserver: User ID: {$user->id} role is '{$user->role}', not 'doctor'. Role not changed.");
                }
            } else {
                Log::info("DoctorObserver: User with ID {$doctor->user_id} not found.");
            }
        } else {
            Log::info("DoctorObserver: Doctor ID: {$doctor->id} had no associated user_id.");
        }
    }

    /**
     * @param  \App\Models\Doctor  $doctor
     * @return void
     */
}
