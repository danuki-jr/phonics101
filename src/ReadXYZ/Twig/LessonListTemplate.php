<?php


namespace ReadXYZ\Twig;


use ReadXYZ\Display\LearningCurve;
use ReadXYZ\Helpers\Util;
use ReadXYZ\Lessons\Lessons;
use ReadXYZ\Models\Cookie;
use ReadXYZ\Models\Student;
use RuntimeException;

class LessonListTemplate
{

    public function __construct()
    {

    }

    public function display()
    {
        $cookie = new Cookie();
        LearningCurve::cleanUpOldGraphics();
        if (!$cookie->tryContinueSession()) {
            throw new RuntimeException("Session has expired.\n" . $cookie->getCookieString());
        }
        $lessons = Lessons::getInstance();
        $studentName = Student::getInstance()->getCapitalizedStudentName();
        $accordion = $lessons->getAccordionList();
        $displayAs = [];
        foreach ($accordion as $group => $lessons) {
            $displayAs[$group] = Util::addSoundClass($group);
            foreach($lessons as $lessonName => $masteryLevel) {
                $displayAs[$lessonName] = Util::addSoundClass($lessonName);
            }
        }

        $args = [
            'accordion' => $accordion,
            'studentName' => $studentName,
            'isLocal' => Util::isLocal(),
            'displayAs' => $displayAs,
            'mostRecentLesson' => $cookie->getCurrentLesson(),
            'mostRecentTab' => $cookie->getCurrentTab()
        ];

        $page = new Page($studentName);
        $page->addArguments($args);

        $page->display('lesson_list');
    }

}
