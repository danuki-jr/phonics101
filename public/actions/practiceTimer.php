<?php

// we only use $_REQUEST['seconds']. We already know the current lesson and student.
use ReadXYZ\Helpers\Util;
use ReadXYZ\Models\Cookie;
use ReadXYZ\Models\Student;
use ReadXYZ\Twig\LessonTemplate;

require 'autoload.php';

if (Util::isLocal()) {
    error_reporting(E_ALL | E_STRICT);
}
$cookie = new Cookie();
if (!$cookie->tryContinueSession()) {
    throw new RuntimeException("Session not found.\n" . $cookie->getCookieString());
}
$student = Student::getInstance();

$currentLessonName = $cookie->getCurrentLesson();
$seconds = $_REQUEST['seconds'] ?? 0;
// if ($seconds) {
//     $student->cargo['currentLessons'][$currentLessonName]['practiceCurve'][time()] = $seconds;
//     while (count($student->cargo['currentLessons'][$currentLessonName]['practiceCurve']) > 8) {
//         array_shift($student->cargo['currentLessons'][$currentLessonName]['practiceCurve']);
//     }
// } else {
//     $studentName = $student->getCapitalizedStudentName();
//     error_log("Fluency timed test for $studentName was 0.");
// }

$lessonTemplate = new LessonTemplate($currentLessonName, 'fluency');
$lessonTemplate->display();
