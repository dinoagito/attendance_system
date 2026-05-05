<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\StudentAttendance;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test students
        $students = [
            [
                'student_id_number' => 'STU-2024-001',
                'rfid_uid' => 'RFID001',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'course' => 'CS-101',
                'section' => 'A',
            ],
            [
                'student_id_number' => 'STU-2024-002',
                'rfid_uid' => 'RFID002',
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'course' => 'CS-101',
                'section' => 'A',
            ],
            [
                'student_id_number' => 'STU-2024-025',
                'rfid_uid' => 'RFID025',
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'course' => 'EN-102',
                'section' => 'B',
            ],
            [
                'student_id_number' => 'STU-2024-078',
                'rfid_uid' => 'RFID078',
                'first_name' => 'Emily',
                'last_name' => 'Davis',
                'course' => 'MA-103',
                'section' => 'C',
            ],
            [
                'student_id_number' => 'STU-2024-090',
                'rfid_uid' => 'RFID090',
                'first_name' => 'Robert',
                'last_name' => 'Wilson',
                'course' => 'CS-101',
                'section' => 'A',
            ],
        ];

        foreach ($students as $studentData) {
            Student::create($studentData);
        }

        // Create test attendance records
        $students = Student::all();
        
        foreach ($students as $student) {
            // Present on 2025-12-10
            StudentAttendance::create([
                'student_id' => $student->id,
                'date' => '2025-12-10',
                'time_in' => '08:15:00',
                'time_out' => '15:45:00',
                'status' => 'present',
            ]);

            // Late on 2025-12-09
            StudentAttendance::create([
                'student_id' => $student->id,
                'date' => '2025-12-09',
                'time_in' => '08:32:00',
                'time_out' => '16:15:00',
                'status' => 'late',
            ]);

            // Absent on 2025-12-08
            StudentAttendance::create([
                'student_id' => $student->id,
                'date' => '2025-12-08',
                'time_in' => null,
                'time_out' => null,
                'status' => 'absent',
            ]);
        }
    }
}
