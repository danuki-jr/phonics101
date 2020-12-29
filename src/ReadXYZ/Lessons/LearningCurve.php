<?php

namespace App\ReadXYZ\Lessons;

use App\ReadXYZ\Models\Log;
use Color;
use Point;
use App\ReadXYZ\Helpers\Util;
use VerticalBarChart;
use XYDataSet;

class LearningCurve
{
    public array $data = [];

    public function learningCurveChart($input)
    {      // creates .PNG,  returns filename for <IMG /> tag
        assert(is_array($input));
        assert(count($input) > 0, 'Did not expect an empty graph for Learning Curve graph');

        foreach($input as $datum) {$this->data[] = $datum->seconds;}
        // save it for others
        require_once dirname(dirname(__DIR__)).'/3rdParty/libChart/classes/libChart.php';
        $chart = new VerticalBarChart(140 + (25 * count($this->data)), 250);
        $chart->getPlot()->getPalette()->setBarColor([new Color(0, 170, 176), new Color(0, 170, 176)]);
        $dataSet = new XYDataSet();
        $i = 1;
        $min = 0;

        //$min = $this->data[0];                      // find the smallest value of the data
        //if(count($this->data) == 1)    $min = 0;     // special case

        foreach ($this->data as $dataPoint) {
            $dataSet->addPoint(new Point($i++, $dataPoint));
            $min = min($min, max($dataPoint - 5, 0));
        }

        $chart->setDataSet($dataSet);
        $chart->bound->setLowerBound($min);
        $chart->setTitle('Time (Seconds)');

        $generatedCache = Util::getPublicPath('generated');
        if (!is_dir($generatedCache)) {
            mkdir($generatedCache);
        }

        $uuid = uniqid();      // can't reuse the filename since multiple users
        $filename = Util::getPublicPath("generated/$uuid.png");    // save to disk
        $urlName = "/generated/$uuid.png";    // refer via browser

        $chart->render($filename);
        $dataPoints = count($this->data);
        Log::info("$dataPoints saved to $filename");
        return $urlName;
    }

    public static function cleanUpOldGraphics()
    {
        // clean up old files
        $generatedCache = Util::getPublicPath('generated');
        if (!is_dir($generatedCache)) {
            mkdir($generatedCache);
        }
        $files = glob("$generatedCache/*.png");
        $totalCt = count($files);
        $failedCt = $successCt = 0;
        $cutoffTime = time() - (60 * 60 * 48);
        foreach ($files as $file) {
            $modTime = filemtime($file);
            if ($modTime < $cutoffTime) {
                $result = unlink($file);
                if ($result) {$successCt++;} else {$failedCt++;}
            }
        }
        $files = glob("$generatedCache/*.png");
        $leftCt = count($files);

        Log::info("$totalCt files inspected in $generatedCache. Failed to delete $failedCt. Successfully deleted $successCt. Files remaining $leftCt.");
        if ($failedCt > 0) Util::redBox("failed to delete $failedCt files in generated folder.");

    }
}