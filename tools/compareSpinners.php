<?php

// compares spinner data between the groupedLessons.json and blendingLessons.json

require dirname(__DIR__) . '/src/ReadXYZ/autoload.php';

if (not(defined('CLASS'))) {
    define('CLASS', 0);
    define('LAYOUT', 1);
    define('STYLE', 2);
    define('TAB_NAME', 3);
    define('METHOD', 4);
    define('DATA', 5);
    define('NOTE', 6);
}

use ReadXYZ\Helpers\Util;
use ReadXYZ\Lessons\BlendingInfo;
use ReadXYZ\Lessons\LessonInfo;

function getBlendingWordLists(array $blendingLessons)
{
    $wordListTabs = ['Words', 'Practice', 'Test'];
    $wordLists = [];
    foreach ($blendingLessons as $blendingLesson) {
        $list = '';
        $lessonName = $blendingLesson['lessonName'];
        foreach ($blendingLesson['pages'] as $page) {
            if (in_array($page[TAB_NAME], $wordListTabs)) {
                foreach ($page[DATA] as $listPart) {
                    $cleanList = preg_replace('/\s+/', '', $listPart);
                    $list .= (',' . $cleanList);
                }
                $list = substr($list, 1);
                break;
            }
        }
        $wordLists[$lessonName] = $list;
    }

    return $wordLists;
}

function printArray(array $array): void
{
    foreach ($array as $item) {
        printf("%s\n", $item);
    }
}

function printTabNames(array $blendingLessons): void
{
    $tabNames = getTabNames($blendingLessons);
    printArray($tabNames);
}

function getTabNames(array $blendingLessons): array
{
    $tabs = [];
    foreach ($blendingLessons as $blendingLesson) {
        foreach ($blendingLesson['pages'] as $page) {
            $tabs[] = $page[TAB_NAME];
        }
    }
    $tabs = array_unique($tabs);
    sort($tabs);

    return array_values($tabs);
}

function makeWordListComparisionCsv(): void
{
    $blendingInfo = BlendingInfo::getInstance();
    $lessonInfo = LessonInfo::getInstance();
    $blendingLessons = $blendingInfo->getAllLessons();
    $blendingWordLists = getBlendingWordLists($blendingLessons);
    $jsonWordLists = $lessonInfo->getAllWordLists();
    printf("\n\nLessonKey, BlendingWordList, JsonWordList\n");
    foreach ($blendingWordLists as $key => $blendingWordList) {
        printf("\"%s\",\"%s\",\"%s\"\n", $key, $blendingWordList, $jsonWordLists[$key] ?? '');
    }
}

function makeSpinnerComparisionCsv(): void
{
    $blendingInfo = BlendingInfo::getInstance();
    $lessonInfo = LessonInfo::getInstance();
    $blendingLessons = $blendingInfo->getAllLessons();
    printf("\n\nLessonKey, PrefixBlending, VowelBlending, SuffixBlending\n");
    foreach ($blendingLessons as $blendingLesson) {
        $lessonKey = $blendingLesson['lessonKey'];
        $lessonName = $blendingLesson['lessonName'];
        $bs = $blendingInfo->getSpinner($lessonKey);
        printf("\"%s\",\"%s\",\"%s\",\"%s\"\n", $lessonName, $bs['prefixes'], $bs['vowel'], $bs['suffix']);
    }
    printf("\n\n\nLessonKey, PrefixJson, VowelJson, SuffixJson\n");
    foreach ($blendingLessons as $blendingLesson) {
        $lessonName = $blendingLesson['lessonName'];
        $js = $lessonInfo->getSpinner($lessonName);
        printf("\"%s\",\"%s\",\"%s\",\"%s\"\n", $lessonName, $js['prefixes'], $js['vowel'], $js['suffix']);
    }
}

function printOldLessonNamesComplete(): void
{
    $lessonInfo = LessonInfo::getInstance();
    $jsonLessons = $lessonInfo->getAllLessons();
    foreach ($jsonLessons as $lesson) {
        $groupName = $lessonInfo->getGroupName($lesson['lessonName']);
        printf("%s: %s -> %s\n", $groupName, $lesson['alternateLessonNames'][0], $lesson['lessonName']);
    }
}

function printOldLessonNames(): void
{
    $lessonInfo = LessonInfo::getInstance();
    $jsonLessons = $lessonInfo->getAllLessons();
    foreach ($jsonLessons as $lesson) {
        printf("%s\n", $lesson['alternateLessonNames'][0]);
    }
}

function printBlendingLessonNames()
{
    $lessonGroups = BlendingInfo::getInstance()->getAllLessonGroups();
    foreach ($lessonGroups as $lesson => $group) {
        printf("%-25s: %s\n", $group, $lesson);
    }
}

Util::fakeLogin();

//$array = Util::csvFileToArray('c:/users/carlb/desktop/compare.csv');
// makeSpinnerComparisionCsv($blendingInfo, $lessonInfo);
//printOldLessonNames();
printBlendingLessonNames();