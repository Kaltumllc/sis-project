<?php
/**
 * GPA Calculation Logic for SIS
 * Converts Letter Grades to 4.0 Scale
 */
function calculateGPA($conn, $student_id) {
    $grade_points = [
        'A'  => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B'  => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C'  => 2.0, 'C-' => 1.7,
        'D+' => 1.3, 'D'  => 1.0, 'F'  => 0.0
    ];

    $sid = mysqli_real_escape_string($conn, $student_id);
    $result = mysqli_query($conn, "SELECT grade FROM grades WHERE student_id = '$sid'");

    $total_points = 0;
    $count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $grade = strtoupper(trim($row['grade']));
        if (isset($grade_points[$grade])) {
            $total_points += $grade_points[$grade];
            $count++;
        }
    }
    return ($count > 0) ? number_format($total_points / $count, 2) : "0.00";
}

function getGPAColor($gpa) {
    if ($gpa >= 3.5) return '#16a34a';
    if ($gpa >= 3.0) return '#2563eb';
    if ($gpa >= 2.0) return '#d97706';
    return '#dc2626';
}

function getGPALabel($gpa) {
    if ($gpa >= 3.7) return "Summa Cum Laude";
    if ($gpa >= 3.5) return "Dean's List";
    if ($gpa >= 3.0) return "Good Standing";
    if ($gpa >= 2.0) return "Satisfactory";
    if ($gpa >  0)   return "Academic Warning";
    return "No Grades Yet";
}
?>