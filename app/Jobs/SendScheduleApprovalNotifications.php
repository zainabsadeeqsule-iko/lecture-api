<?php

namespace App\Jobs;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendScheduleApprovalNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $schedule;

    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    public function handle()
    {
        // Get the lecturer and department for the approved schedule
        $lecturer = $this->schedule->lecturer;
        Log::info('Retrieved lecturer: ' . $lecturer->name);

        $department = $this->schedule->course->department;
        Log::info('Retrieved department: ' . $department->name);

        // Eager load the students relationship
        $department->load('students');
        Log::info('Loaded students for department: ' . $department->name);

        // Send notification to the lecturer
        $this->sendNotification($lecturer->phone, "Hi {$lecturer->name}, your schedule for {$this->schedule->course->name} on {$this->schedule->schedule_date} from {$this->schedule->start_time} to {$this->schedule->end_time} has been approved by the Admin. See you in class !");

        // Get the students for the department
        $students = $department->students;
        Log::info('Number of students in department ' . $department->name . ': ' . $students->count());

        // Send notifications to the students if there are any
        if ($students->isNotEmpty()) {
            foreach ($students as $student) {
                Log::info('Sending notification to student: ' . $student->name);
                $this->sendNotification($student->phone, "Hi {$student->name}, Your Lecturer {$lecturer->name} has added a new schedule for {$this->schedule->course->name} on {$this->schedule->schedule_date} from {$this->schedule->start_time} to {$this->schedule->end_time} has been added.");
            }
        } else {
            Log::info('No students found for the department: ' . $department->name);
        }
    }

    private function sendNotification($phoneNumber, $message)
    {
        try {
            // Set the API endpoint URL
            $url = 'https://smsclone.com/api/sms/sendsms';

            // Set the API parameters
            $params = [
                'username' => 'remindme',
                'password' => 'mydzaf-dakbyg-0foxsY',
                'sender' => 'REMINDME',
                'recipient' => $phoneNumber,
                'message' => $message,
            ];

            // Make the API request
            $response = Http::get($url, $params);

            // Check the response status
            if ($response->successful()) {
                // Handle successful response
                $responseData = $response->json();
                Log::info('SMS notification sent successfully.', [
                    'phone_number' => $phoneNumber,
                    'message' => $message,
                    'response' => $responseData,
                ]);
                // Process the response data as needed
            } else {
                // Handle error response
                $errorMessage = $response->body();
                Log::error('Failed to send SMS notification.', [
                    'phone_number' => $phoneNumber,
                    'message' => $message,
                    'error' => $errorMessage,
                ]);
                // Log the error or take appropriate action
            }
        } catch (\Exception $e) {
            // Handle and log any exceptions
            Log::error('Exception occurred while sending SMS notification.', [
                'phone_number' => $phoneNumber,
                'message' => $message,
                'exception' => $e->getMessage(),
            ]);
            // You can also choose to re-throw the exception if needed
            // throw $e;
        }
    }
}
