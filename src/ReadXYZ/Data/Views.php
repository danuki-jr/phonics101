<?php


namespace App\ReadXYZ\Data;


use App\ReadXYZ\Enum\QueryType;
use LogicException;
use stdClass;

class Views extends AbstractData
{
    public function __construct()
    {
        parent::__construct('', '');
    }

    // /**
    //  * Fields are lessonCode, lessonName, lessonDisplayAs, groupCode, groupName, mastery, studentCode
    //  * @param string $studentCode
    //  * @return stdClass[]
    //  */
    // public function getAccordionView(string $studentCode): array
    // {
    //     $query = "SELECT * FROM vw_accordion WHERE studentCode = '$studentCode'";
    //     return parent::throwableQuery($query, QueryType::STDCLASS_OBJECTS);
    // }

    public function doesTrainerHaveStudents(string $user): bool
    {
        $query = "SELECT * FROM vw_students_with_username WHERE trainerCode = '$user' OR userName = '$user'";
        return parent::throwableQuery($query, QueryType::EXISTS);
    }

    public function isValidStudentTrainerPair(string $user, string $student): bool
    {
        $where = "(studentCode = '$student' OR studentName = '$student') AND (userName = '$user' OR trainerCode = '$user')";
        $query = "SELECT * FROM vw_students_with_username WHERE $where";
        return parent::throwableQuery($query, QueryType::EXISTS);
    }


    // ------------ DELETED FUNCTIONS -----------------------------------
    public function deleteOne($keyValue): void { $this->notImplemented(); }
    public function updateOne($keyValue, string $fieldName, $newValue): void { $this->notImplemented(); }
    public function throwableQuery(string $query, $queryType, bool $throwOnNotFound = false)
    {
        throw new LogicException("Invalid method for this class.");
    }
    public function query(string $query, $queryType): DbResult
    {
        throw new LogicException("Invalid method for this class.");
    }

    protected function baseDelete(string $where, int $foreignKeyChecks=0): int { $this->notImplemented(); return 0;}
    protected function truncate(): int { $this->notImplemented(); return 0;}
    private function notImplemented()
    {
        throw new LogicException("Invalid method for this class.");
    }

}
