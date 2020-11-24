<?php


namespace App\ReadXYZ\Data;


use App\ReadXYZ\Enum\QueryType;
use App\ReadXYZ\Enum\Sql;
use App\ReadXYZ\Helpers\Util;
use Exception;
use LogicException;
use stdClass;

abstract class AbstractData
{
    protected PhonicsDb $db;
    protected string $tableName;
    protected string $primaryKey;
    protected array  $booleanFields = [];
    protected array  $jsonFields = [];
    /**
     * AbstractData constructor.
     * @param string $tableName used by deleteOne, updateOne, getCount and truncate
     * @param string $primaryKey used by deleteOne and updateOne
     * @param int $dbVersion specifies using readxyz1_1 (1 or default) or readxyz0_1 (0)
     */
    public function __construct(string $tableName, string $primaryKey='id', int $dbVersion=Sql::READXYZ1_1)
    {
        // header is required because sendResponse fails without it
        if ($dbVersion != Sql::READXYZ1_1 && $dbVersion != Sql::READXYZ0_1) {
            throw new LogicException("$dbVersion is not a valid database version.");
        }
        $this->db = new PhonicsDb($dbVersion);
        $this->tableName = $tableName;
        $this->primaryKey = $primaryKey;
    }

    protected function fixObject(stdClass $object): stdClass
    {
        $newObject = $object;
        foreach( $this->booleanFields as $field) {
            try {
                if (isset($newObject->$field) && isset($object->$field)) {
                    $newObject->$field = $this->enumToBool($object->$field);
                }
            } catch( Exception $ex) {
                throw new LogicException("We should never get here. " . $ex->getMessage());
            }

        }
        foreach ($this->jsonFields as $field) {
            if (isset($newObject->$field) && isset($object->$field) && is_string($object>$field)) {
                try {
                    $newObject->$field  = is_null($object->$field) ? NULL : json_decode($object->$field);
                } catch (Exception $ex) {
                    throw new LogicException("We should never get here. " . $ex->getMessage());
                }

            }

        }
        return $newObject;
    }

// ======================== PUBLIC METHODS =====================

    /**
     * Delete a single record whose primary key matches the specified value
     * @param mixed $keyValue
     */
    public function deleteOne($keyValue): void
    {
        $smartValue = $this->smartQuotes($keyValue);
        $query = "DELETE FROM {$this->tableName} WHERE {$this->primaryKey} = $smartValue";
        $this->throwableQuery($query, QueryType::STATEMENT);
    }

    /**
     * @return int the number of records in the table.
     */
    public function getCount(): int
    {
        $result = $this->query("SELECT * FROM {$this->tableName}", QueryType::RECORD_COUNT);
        return $result->wasSuccessful() ? $result->getResult() : 0;
    }

    /**
     * @param mixed $keyValue the value of the Primary Key we want.
     * @param string $fieldName the name of the field to be updated.
     * @param $newValue the new value for the field.
     */
    public function updateOne($keyValue, string $fieldName, $newValue): void
    {
        $smartValue = $this->smartQuotes($newValue);
        $smartKey = $this->smartQuotes($keyValue);
        $query = "UPDATE {$this->tableName} SET $fieldName = $smartValue WHERE {$this->primaryKey} = $smartKey";
        $this->throwableQuery($query, QueryType::STATEMENT);
    }

    /**
     * @param string $query
     * @param QueryType|string $queryType
     * @return DbResult
     */
    public function query(string $query, $queryType): DbResult
    {
        if ($queryType instanceof QueryType) {
            $type = $queryType->getValue();
        } else {
            if (QueryType::isValid($queryType)) {
                $type = $queryType;
            } else {
                throw new LogicException("$queryType not a valid query type.");
            }
        }
        switch ($type) {
            case QueryType::ASSOCIATIVE_ARRAY:
                return $this->db->queryRows($query);
            case QueryType::STDCLASS_OBJECTS:
                $result = $this->db->queryObjects($query);
                if ($result->wasSuccessful()) {
                    $newResult = [];
                    $objects = $result->getResult();
                    foreach($objects as $object) {
                        $newResult[] = $this->fixObject($object);
                    }
                    return DbResult::goodResult($newResult);
                } else {
                    return $result;
                }
            case QueryType::SCALAR:
                return $this->db->queryAndGetScalar($query);
            case QueryType::RECORD_COUNT:
                return $this->db->queryAndGetCount($query);
            case QueryType::EXISTS:
                $result = $this->db->queryAndGetCount($query);
                return $result->wasSuccessful() ? DbResult::goodResult($result->getResult() > 0) : $result;
            case QueryType::SCALAR_ARRAY:
                return $this->db->queryAndGetScalarArray($query);
            case QueryType::SINGLE_RECORD:
                return $this->db->queryRecord($query);
            case QueryType::SINGLE_OBJECT:
                $result = $this->db->queryObject($query);
                if ($result->wasSuccessful()) {
                    return DbResult::goodResult($this->fixObject($result->getResult()));
                } else {
                    return $result;
                }

            case QueryType::AFFECTED_COUNT:
                return $this->db->queryAndGetAffectedCount($query);
            case QueryType::STATEMENT:
                $result = $this->db->queryStatement($query);
                if ($result->wasSuccessful()) {
                    return DbResult::goodResult(1);
                } else {
                    return DbResult::badResult($result->getErrorMessage());
                }
            default:
                return DbResult::badResult($queryType->getValue() . ' is not a valid record type.');
        }
    }

    /**
     *
     * @param mixed $value
     * @return mixed
     */
    public function smartQuotes($value): string
    {
        if ( ! is_numeric($value) || '0' == $value[0]) {
            $value = "'" . $this->db->getConnection()->real_escape_string($value) . "'";
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function boolToEnum($value): string
    {
        if (is_null($value)) return 'F';
        if (is_string($value)) {
            if ($value == Sql::ACTIVE || $value == Sql::INACTIVE) {
                return $value;
            } else {
                throw new LogicException("$value is not a valid value for Active");
            }
        } else if (is_bool($value)) {
            return $value ? 'Y' : 'N';
        } else {
            throw new LogicException("$value must be 'Y', 'N' , true or false");
        }

    }

    /**
     * We expect $enum to be Sql::ACTIVE or Sql::Inactive but we'll accept a bool value as well
     * @param $enum
     * @return bool
     */
    public function enumToBool($enum): bool
    {
        if (is_null($enum)) return false;
        if (is_string($enum)) {
            if ($enum == Sql::ACTIVE || $enum == Sql::INACTIVE) {
                return $enum == 'Y';
            } else {
                throw new LogicException("$enum is not a valid string value for Active");
            }
        } else if (is_bool($enum)) {
            return $enum;
        } else {
            throw new LogicException("$enum must be 'Y', 'N' , true or false");
        }
    }

    /**
     * executes the query, returns the result or throws if query failed.
     * If $throwIfNotFound is true then throw if the 0 records match.
     * @param string $query
     * @param QueryType|string $queryType
     * @param bool $throwOnNotFound
     * @return mixed
     */
    public function throwableQuery(string $query, $queryType, bool $throwOnNotFound = false)
    {
        $queryType = $this->getQueryTypeValue($queryType);
        $result = $this->query($query, $queryType);
        if ($result->wasSuccessful()) {
            $goodResult = $result->getResult();
            switch ($queryType) {
                case QueryType::ASSOCIATIVE_ARRAY:
                case QueryType::SCALAR_ARRAY:
                    if ((count($goodResult) == 0) && $throwOnNotFound) {
                        throw new LogicException('Query found no records.');
                    } else {
                        return $goodResult;
                    }
                case QueryType::STDCLASS_OBJECTS:
                    $newResult = [];
                    $objects = $goodResult;
                    foreach($objects as $object) {
                        $newResult[] = $this->fixObject($object);
                    }
                    return $newResult;
                case QueryType::SCALAR:
                case QueryType::SINGLE_RECORD:
                    if (($goodResult == null) && $throwOnNotFound) {
                        throw new LogicException('Query found no records.');
                    } else {
                        return $goodResult;
                    }
                case QueryType::SINGLE_OBJECT:
                    if (($goodResult == null) && $throwOnNotFound) {
                        throw new LogicException('Query found no records.');
                    } else {
                        return $this->fixObject($goodResult);
                    }
                case QueryType::RECORD_COUNT:
                case QueryType::AFFECTED_COUNT:
                case QueryType::EXISTS:
                    return $goodResult;
                default:
                    return DbResult::badResult($queryType . ' is not a valid query type.');
            }
        } else {
            throw new LogicException($result->getErrorMessage());
        }
    }

// ======================== PROTECTED METHODS =====================

    /**
     * Performs a SQL DELETE. You must be in standalone mode to have an empty where clause or
     * to turn foreign key checks off.
     * @param string $whereClause WHERE clause
     * @param int $foreignKeyChecks sql::FOREIGN_KEY_CHECKS_ON or sql::FOREIGN_KEY_CHECKS_OFF
     * @return int  the number of records affected
     */
    protected function baseDelete(string $whereClause, int $foreignKeyChecks=Sql::FOREIGN_KEY_CHECKS_ON): int
    {
        if (!runningStandalone()) {
            if (empty($whereClause) || ($foreignKeyChecks == Sql::FOREIGN_KEY_CHECKS_OFF)) {
                throw new LogicException('Empty where clause and/or key check off only allowed in standalone environment');
            }
        }
        if (not(empty($whereClause)) && not(Util::startsWith_ci(ltrim($whereClause), 'WHERE'))) {
            $whereClause = 'WHERE ' . $whereClause;
        }
        if ($foreignKeyChecks == Sql::FOREIGN_KEY_CHECKS_OFF) {
            $this->throwableQuery("SET FOREIGN_KEY_CHECKS = 0", QueryType::STATEMENT);
        }
        $query = "DELETE FROM {$this->tableName} $whereClause";
        $count = $this->throwableQuery($query, QueryType::AFFECTED_COUNT);
        if ($foreignKeyChecks == Sql::FOREIGN_KEY_CHECKS_OFF) {
            $this->throwableQuery("SET FOREIGN_KEY_CHECKS = 1", QueryType::STATEMENT);
        }
        return $count;
    }
    /**
     * smart quotes for JSON object
     * @param $object
     * @return string suitable for use as the value of a mysql JSON field
     */
    protected function encodeJsonQuoted($object)
    {
        if ($object == null) {
            return "NULL";
        }
        $encode = json_encode($object, JSON_UNESCAPED_SLASHES);
        $fixed = str_replace("'", "\\'", $encode);
        return "'" . $fixed . "'";
    }

    /**
     * @param string|QueryType $queryType
     * @return string
     */
    protected function getQueryTypeValue($queryType): string
    {
        if ($queryType instanceof QueryType) return $queryType->getValue();
        if (QueryType::isValid($queryType)) return $queryType;
        throw new LogicException("$queryType not a valid query type.");
    }

    /**
     * @param int $http_code the http code we want the response to send
     * @param string $msg the message we want the response to return (default: OK)
     */
    protected function sendResponse(int $http_code = 200, string $msg = 'OK'): void
    {
        header('Content-Type: application/json');
        http_response_code($http_code);
        echo json_encode(['code' => $http_code, 'msg' => $msg]);
    }

    /**
     * Deletes all the records in a table (Not reversible)
     * @return int
     */
    protected function truncate(): int
    {
        return $this->baseDelete('', sql::FOREIGN_KEY_CHECKS_OFF);
    }

}
