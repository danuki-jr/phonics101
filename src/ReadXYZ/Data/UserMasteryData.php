<?php


namespace App\ReadXYZ\Data;


use App\ReadXYZ\Helpers\Util;
use App\ReadXYZ\Models\BoolWithMessage;
use App\ReadXYZ\Models\Log;
use App\ReadXYZ\Models\Session;
use RuntimeException;

class UserMasteryData extends AbstractData
{

    private PhonicsDb $db;

    public function __construct()
    {
        parent::__construct('abc_usermastery');
    }


    public function update(string $studentId, string $presentedWordList, array $masteredWords): BoolWithMessage
    {
        $conn = $this->db->getConnection();
        $quotedList = Util::quoteList($presentedWordList);
        $query = "DELETE FROM abc_usermastery WHERE studentID = '$studentId' AND word IN ($quotedList)";
        $conn->begin_transaction();
        $result = $this->db->queryStatement($query);
        if ($result->failed()) {
            $error_message = "Query failed: {$this->db->getErrorMessage()} ::: $query";
            $conn->rollback();
            return BoolWithMessage::badResult($error_message);
        } else {
            foreach ($masteredWords as $word) {
                $query = "INSERT INTO abc_usermastery (studentID, word) VALUES ('$studentId', '$word')";
                $result =  $this->db->queryStatement($query);
                if ($result->failed()) {
                    $conn->rollback();
                    return $result;
                }
            }
            $conn->commit();
            return BoolWithMessage::goodResult();
        }
    }

    public function processRequest()
    {
        $session = new Session();
        if (!$session->hasLesson()) {
            throw new RuntimeException('Cannot update user mastery without an active lesson.');
        }
        $studentID = $session->getStudentId();
        $presentedWordList = $_REQUEST['wordlist'];
        $masteredWords = $_REQUEST['word1'] ?? [];
        $result = $this->update($studentID, $presentedWordList, $masteredWords);

        if ($result->wasSuccessful()) {
            $this->sendResponse(200, 'Update successful');
        } else {
            $msg = $result->getErrorMessage();
            Log::error($msg);
            $this->sendResponse(500, $msg);
        }
        exit();
    }

    public function getMasteredWords(): array
    {
        $session = new Session();
        if (!$session->hasLesson()) {
            throw new RuntimeException('Cannot get mastered words without an active lesson.');
        }
        $studentId = $session->getStudentId();
        $query = "SELECT word from abc_usermastery WHERE studentID=$studentId";
        $result = $this->db->queryAndGetScalarArray($query);
        return $result->wasSuccessful() ? $result->getResult() : [];
    }

}
