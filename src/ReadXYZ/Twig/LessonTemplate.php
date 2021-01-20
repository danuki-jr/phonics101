<?php


namespace App\ReadXYZ\Twig;


use App\ReadXYZ\Data\Views;
use App\ReadXYZ\Data\WordMasteryData;
use App\ReadXYZ\Helpers\PhonicsException;
use App\ReadXYZ\Helpers\ScreenCookie;
use App\ReadXYZ\Helpers\Util;
use App\ReadXYZ\JSON\LessonsJson;
use App\ReadXYZ\JSON\TabTypesJson;
use App\ReadXYZ\JSON\WarmupsJson;
use App\ReadXYZ\Lessons\SideNote;
use App\ReadXYZ\Models\BreadCrumbs;
use App\ReadXYZ\Models\Session;
use App\ReadXYZ\Page\LessonPage;
use App\ReadXYZ\Lessons\Lesson;

class LessonTemplate
{
    private LessonsJson    $lessonsJson;
    private ?Lesson    $lesson;
    private LessonPage $page;
    private string     $lessonName;
    private string     $initialTabName;
    private string     $trainerCode;

    /**
     * LessonTemplate constructor.
     * @param string $lessonName
     * @param string $initialTabName
     * @throws PhonicsException on ill-formed SQL
     */
    public function __construct(string $lessonName = '', string $initialTabName = '')
    {
        $this->lessonsJson = LessonsJson::getInstance();
        $this->lessonName  = $lessonName;
        $this->initialTabName = $initialTabName;
        $this->trainerCode    = Session::getTrainerCode();
        Session::updateLesson($lessonName);

        $studentName = Session::getStudentName();
        $this->lesson = $this->lessonsJson->getLesson($lessonName);
        if ($this->lesson == null) {
            return Util::redBox("A lesson named $lessonName does not exist.");
        }
        $this->page = new LessonPage($lessonName, $studentName);
    }

// ======================== PUBLIC METHODS =====================

    /**
     * @param string $initialTab
     * @throws PhonicsException on ill-formed SQL
     */
    public function display(string $initialTab=''): void
    {
        if ($initialTab) {
            $this->initialTabName = $initialTab;
        }
        $sideNote              = SideNote::getInstance();
        $args                  = [];
        $args['students']      = Views::getInstance()->getStudentNamesForUser($this->trainerCode);
        $args['page']          = $this->page;
        $args['lesson']        = $this->lesson;
        $args['tabTypes']      = TabTypesJson::getInstance();
        $args['isSmallScreen'] = ScreenCookie::isScreenSizeSmall();
        $args['sideNote']      = SideNote::getInstance();
        $args['testCurve']     = $sideNote->getTestCurveHTML();
        $args['learningCurve'] = $sideNote->getLearningCurveHTML();
        $args['masteredWords'] = (new WordMasteryData())->getMasteredWords();
        $args['this_crumb'] = $this->lesson->lessonName;
        if ($this->initialTabName) {
            $args['initialTabName'] = $this->initialTabName;
        }
        if (in_array('warmup', $this->lesson->tabNames)) {
            $args['warmups']   = WarmupsJson::getInstance()->get($this->lesson->lessonId);
        }
        if (isset($this->lesson->soundLetters)) {
            $args['soundLetters'] = $this->lesson->soundLetters;
        } else {
            $args['soundLetters'] = str_split('abcdefghijklmnopqrstuvwxyz');
        }

        $breadcrumbs = (new BreadCrumbs())->getPrevious('lesson');
        if ($breadcrumbs) $args['previous_crumbs'] = $breadcrumbs;
        $this->page->addArguments($args);
        $this->page->displayLesson();
    }

}
