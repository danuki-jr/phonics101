<?php
// This is the target for the fluency timer and test timer

// we only use $_REQUEST['seconds']. We already know the current lesson and student.
use ReadXYZ\Database\LessonResults;
use ReadXYZ\Helpers\Util;
use ReadXYZ\Models\Cookie;
use ReadXYZ\Models\Student;
use ReadXYZ\Twig\LessonTemplate;
use ReadXYZ\Twig\LoginTemplate;

require 'autoload.php';

if (Util::isLocal()) {
    error_reporting(E_ALL | E_STRICT);
}
$cookie = new Cookie();
if (!$cookie->tryContinueSession()) {
    (new LoginTemplate("Login has expired (fluency timer).\n" . $cookie->getCookieString()))->display();
    exit;
}
$student = Student::getInstance();
$studentName = $student->getCapitalizedStudentName();
$currentLessonName = $cookie->getCurrentLesson();
$student->saveLessonSelection($currentLessonName);
$source = $_REQUEST['source'] ?? 'unknown';
$seconds = $_REQUEST['seconds'] ?? 0;
$tab = ('fluency' == $source) ? 'fluency' : 'test';

$lessonTemplate = new LessonTemplate($currentLessonName, $tab);

if ('fluency' == $source) {
    if ($seconds) {
        $student->cargo['currentLessons'][$currentLessonName]['learningCurve'][time()] = $seconds;
        while (count($student->cargo['currentLessons'][$currentLessonName]['learningCurve']) > 8) {
            array_shift($student->cargo['currentLessons'][$currentLessonName]['learningCurve']);
        }
    } else {
        $studentName = $student->getCapitalizedStudentName();
        error_log("Fluency timed test for $studentName was 0.");
    }


    $lessonTemplate->display();
} elseif ('test' == $source) {
    $assumedLessonName = $student->prepareCurrentForUpdate();
    $seconds = intval($_REQUEST['seconds'] ?? '0');
    $student->updateLearningCurveCargo($assumedLessonName, $seconds);

    $lessonTemplate->display();
} elseif ('testMastery' == $source) {
    $assumedLessonName = $student->prepareCurrentForUpdate();
    $masteryType = $_REQUEST['masteryType'];
    LessonResults::getInstance()->write($_POST);
    switch ($masteryType) {
        case 'Advancing':
            $student->cargo['currentLessons'][$currentLessonName]['mastery'] = 1;
            //If it was previously mastered get rid of the mastery entry;
            if (isset($student->cargo['masteredLessons'][$currentLessonName])) {
                unset($student->cargo['masteredLessons'][$currentLessonName]);
            }
            break;
        case 'Mastered':
            // you can't just copy an array, you need to CLONE it
            $cloneObject = new ArrayObject(
                $student->cargo['currentLessons'][$currentLessonName]
            );
            $clone = $cloneObject->getArrayCopy();
            $clone['mastery'] = 5;
            $student->cargo['masteredLessons'][$currentLessonName] = $clone;
            unset($student->cargo['currentLessons'][$currentLessonName]); // won't affect the clone
            break;

        default:
            assert(false, "Did not expect '$masteryType' as a submit type");
    }
    $student->saveSession();
    $lessonTemplate->display();

} else {
    $message = "Call to timers.php with unrecognized source $source";
    error_log($message);
    echo Util::redBox($message);
}

