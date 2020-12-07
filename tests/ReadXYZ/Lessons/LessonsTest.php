<?php

namespace Tests\ReadXYZ\Lessons;

use App\ReadXYZ\Helpers\Util;
use Peekmo\JsonPath\JsonStore;
use PHPUnit\Framework\TestCase;
use App\ReadXYZ\Secrets\Access;
use App\ReadXYZ\Lessons\Lessons;

class LessonsTest extends TestCase
{

    public function testGetLessonNamesMap()
    {
        $lessons = Lessons::getInstance();
        $namesMap = $lessons->getLessonNamesMap();
        $this->assertGreaterThan(90, $namesMap);
        $this->assertTrue(isAssociative($namesMap));
    }

    public function testLessonExists()
    {
        $lessons = Lessons::getInstance();
        $inputFile = Util::getReadXyzSourcePath('resources/unifiedLessons.json');
        $json = file_get_contents($inputFile);
        $all = json_decode($json);
        foreach ($all->lessons->blending as $key => $lessonArray) {
            $this->assertTrue($lessons->lessonExists($key));
        }
        $this->assertFalse($lessons->lessonExists(''));
    }

    public function testMaxLengths()
    {
        $lessons = Lessons::getInstance();
        $maxLengths = $lessons->getMaxLengths();
        $this->assertGreaterThan(0, $maxLengths['stretchList']);
    }

    public function testInsertFromJson()
    {
        $inputFile = Util::getReadXyzSourcePath('resources/unifiedLessons.json');
        $json = file_get_contents($inputFile);
        $this->store = new JsonStore($json);
        $all = json_decode($json);
    }

    public function testGetAccordionList()
    {
    }

}
