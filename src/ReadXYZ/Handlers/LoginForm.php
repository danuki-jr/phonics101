<?php


namespace App\ReadXYZ\Handlers;


use App\ReadXYZ\Data\StudentsData;
use App\ReadXYZ\Data\TrainersData;
use App\ReadXYZ\Data\Views;
use App\ReadXYZ\Enum\Regex;
use App\ReadXYZ\Enum\TrainerType;
use App\ReadXYZ\Helpers\PhonicsException;
use App\ReadXYZ\Helpers\Util;
use App\ReadXYZ\Models\RouteMe;
use App\ReadXYZ\Models\Session;
use App\ReadXYZ\Rest\Membership;
use App\ReadXYZ\Twig\LoginTemplate;

class LoginForm extends AbstractHandler
{
    const USING_S2_MEMBER = false;

    /**
     * handles the submission of the login form. The required field username must be supplied. There is an optional
     * student field as well.
     * @throws PhonicsException
     */
    public static function handlePost(): void
    {
        self::fullLocalErrorReportingOn();

        try {
            Session::clearSession();
            $userName = $_POST['username'] ??  '';
            $explicitStudentName = '';
            if (empty($userName)) {
                (new LoginTemplate())->display('Username must be provided.');
                exit;
            }
            $compositeEmail = Regex::parseCompositeEmail($userName);
            $hasStudent = not(empty($compositeEmail->student));
            if ($hasStudent) $explicitStudentName = ucfirst($compositeEmail->student);
            $specialCase = (Util::contains(['carlbaker', 'lisamichelle'], $userName));

            // $user: s2Member stdClass object (userEmail, displayName, students, id, role, (ignore other fields))

            $user = (new Membership())->getUser($userName);
            if (($user->valid !== true) && (! $specialCase)) {
                (new LoginTemplate())->display('Invalid user name.');
                exit;
            }
            // if S2Member knows about the user but he's not in the database or it can supply display name, add or update.
            $trainersData = new TrainersData();
            $changed = false;
            if ($user->valid) {
                $email = $user->userEmail;
                $trainer = $trainersData->get($email);
                $trainerType = TrainerType::TRAINER;
                if ($trainer == null) {
                    if (Util::contains('admin', $user->role)) $trainerType = TrainerType::ADMIN;
                    if (Util::contains('staff', $user->role)) $trainerType = TrainerType::STAFF;
                    $trainersData->add($email, $user->displayName, $trainerType);
                    $changed = true;
                } elseif (empty($trainer->displayName)) {
                    $trainersData->updateName($email, $user->displayName);
                    $trainerType = $trainer->trainerType;
                    $changed = true;
                }
            } else {
                // this only happens if we are a special user
                $email = $compositeEmail->email;
                $trainer = $trainersData->get($email);
                $trainerType = TrainerType::TRAINER;
                if (Util::contains('staff', $userName)) $trainerType = TrainerType::STAFF;
                if (Util::contains('admin', $userName)) $trainerType = TrainerType::ADMIN;
            }



            // retrieve updated trainer record
            if ($changed) {$trainer = $trainersData->get($email);}

            // These are the students in our database
            $views = Views::getInstance();
            $studentData = new StudentsData();
            $ourStudents = $views->getMapOfStudentsForUser($email);
            $ourStudentNames = array_keys($ourStudents);

            // If we are using S2Member to tell us who are students are, we deal with that here.
            // If S2Member knows about students we don't know about, we'll add them to our database.
            if (self::USING_S2_MEMBER) {
                $changed = false;
                $s2students = $user->students; //string[]
                foreach ($s2students as $studentName) {
                    if (!in_array($studentName, $ourStudentNames)) {
                        $studentData->add($studentName, $userName);
                    }
                }
                if ($changed) {
                    $ourStudents = $views->getMapOfStudentsForUser($user->userEmail);
                }
            }

            Session::updateUser($trainer->userName, $trainer->trainerCode, $trainer->displayName, $trainerType, $ourStudents);
            RouteMe::computeImpliedRoute($explicitStudentName);

        } finally {
            self::fullLocalErrorReportingOff();
        }
    }

    public static function handleUserEntry(array $routeParts) {
        print_r($routeParts);
    }
}
