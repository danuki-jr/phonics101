<?php


namespace ReadXYZ\Twig;


use ReadXYZ\Database\StudentTable;
use ReadXYZ\Helpers\ScreenCookie;
use ReadXYZ\Display\LearningCurve;
use ReadXYZ\Helpers\Util;
use ReadXYZ\Lessons\GameTypes;
use ReadXYZ\Lessons\Lesson;
use ReadXYZ\Lessons\LessonPage;
use ReadXYZ\Lessons\Lessons;
use ReadXYZ\Lessons\SideNote;
use ReadXYZ\Lessons\TabTypes;
use ReadXYZ\Lessons\Warmups;
use ReadXYZ\Models\Cookie;
use ReadXYZ\Models\Student;
use RuntimeException;

class LessonTemplate
{

    private ?Lesson $lesson;
    private LessonPage $page;
    private TwigFactory $factory;
    private string $lessonName;
    private string $initialTabName;

    public function __construct(string $lessonName = '', string $initialTabName = '')
    {
        $cookie = Cookie::getInstance();
        LearningCurve::cleanUpOldGraphics();
        if (!$cookie->tryContinueSession()) {
            throw new RuntimeException("Session not found.");
        }
        $this->factory = TwigFactory::getInstance();
        $this->lessonName = $lessonName;
        $this->initialTabName = $initialTabName;
        $cookie->setCurrentLesson($lessonName);
        $lessons = Lessons::getInstance();

        if (empty($lessonName)) {
            $lessonName = $lessons->getCurrentLessonName();
        }
        $student = Student::getInstance();
        if (null === $student) {
            throw new RuntimeException('Student should never be null here.');
        }
        $studentName = $student->getCapitalizedStudentName();

        if (not($lessons->lessonExists($lessonName))) {
            return Util::redBox("A lesson named $lessonName does not exist.");
        }
        $lessons->setCurrentLesson($lessonName);
        $this->lesson = $lessons->getCurrentLesson();
        if (null === $this->lesson) {
            throw new RuntimeException('Lesson should never be null here.');
        }
        $this->page = new LessonPage($lessonName, $studentName);
    }

    public function displayLesson(): void
    {
        $args = [];
        $args['students'] = StudentTable::getInstance()->GetAllStudents();
        $args['warmups'] = Warmups::getInstance()->getLessonWarmup($this->lessonName);
        $args['page'] = $this->page;
        $args['lesson'] = $this->lesson;
        $args['tabTypes'] = TabTypes::getInstance();
        $args['gameTypes'] = GameTypes::getInstance();
        $args['cookie'] = Cookie::getInstance();
        $args['isSmallScreen'] = ScreenCookie::isScreenSizeSmall();
        $args['sideNote'] = SideNote::getInstance();
        $args['masteredWords'] = Student::getInstance()->getMasteredWords();
        echo TwigFactory::getInstance()->renderTemplate('lesson', $args);
    }

}
