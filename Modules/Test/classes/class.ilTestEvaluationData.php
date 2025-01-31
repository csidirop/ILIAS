<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
* Class ilTestEvaluationData
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @author		Björn Heyser <bheyser@databay.de>
* @version		$Id$
*
* @defgroup ModulesTest Modules/Test
* @extends ilObject
*/
class ilTestEvaluationData
{
    /**
    * Question titles
    *
    * @var array
    */
    public $questionTitles;

    /**
    * Test object
    *
    * @var ilObjTest
    */
    private $test;

    /**
    * Participants
    *
    * @var ilTestEvaluationUserData[]
    */
    public $participants;

    /**
    * Statistical data
    *
    * @var object
    */
    public $statistics;

    /**
    * Filter array
    *
    * @var array
    */
    public $arrFilter;

    /**
    *
    * @var integer
    */
    public $datasets;

    /**
     * @var ilTestParticipantList
     */
    protected $accessFilteredParticipantList;

    public function __sleep()
    {
        return array('questionTitles', 'participants', 'statistics', 'arrFilter', 'datasets', 'test');
    }

    /**
    * Constructor
    *
    * @access	public
    */
    public function __construct(ilObjTest $test = null)
    {
        $this->participants = array();
        $this->questionTitles = array();
        if ($test !== null) {
            $this->test = $test;

            if ($this->getTest()->getAccessFilteredParticipantList()) {
                $this->setAccessFilteredParticipantList(
                    $this->getTest()->getAccessFilteredParticipantList()
                );
            }

            $this->generateOverview();
        }
    }

    public function getAccessFilteredParticipantList(): ?ilTestParticipantList
    {
        return $this->accessFilteredParticipantList;
    }

    /**
     * @param ilTestParticipantList $accessFilteredParticipantList
     */
    public function setAccessFilteredParticipantList($accessFilteredParticipantList)
    {
        $this->accessFilteredParticipantList = $accessFilteredParticipantList;
    }

    protected function checkParticipantAccess($activeId): bool
    {
        if ($this->getAccessFilteredParticipantList() === null) {
            return true;
        }

        return $this->getAccessFilteredParticipantList()->isActiveIdInList($activeId);
    }

    protected function loadRows(): array
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */

        $query = "
			SELECT			usr_data.usr_id,
							usr_data.firstname,
							usr_data.lastname,
							usr_data.title,
							usr_data.login,
							tst_pass_result.*,
							tst_active.submitted,
							tst_active.last_finished_pass
			FROM			tst_pass_result, tst_active
			LEFT JOIN		usr_data
			ON				tst_active.user_fi = usr_data.usr_id
			WHERE			tst_active.active_id = tst_pass_result.active_fi
			AND				tst_active.test_fi = %s
			ORDER BY		usr_data.lastname,
							usr_data.firstname,
							tst_pass_result.active_fi,
							tst_pass_result.pass,
							tst_pass_result.tstamp
		";

        $result = $DIC->database()->queryF(
            $query,
            array('integer'),
            array($this->getTest()->getTestId())
        );

        $rows = array();

        while ($row = $DIC->database()->fetchAssoc($result)) {
            if (!$this->checkParticipantAccess($row['active_fi'])) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function generateOverview()
    {
        include_once "./Modules/Test/classes/class.ilTestEvaluationPassData.php";
        include_once "./Modules/Test/classes/class.ilTestEvaluationUserData.php";

        $this->participants = array();

        $pass = null;
        $checked = array();
        $thissets = 0;

        foreach ($this->loadRows() as $row) {
            $thissets++;

            $remove = false;

            if (!$this->participantExists($row["active_fi"])) {
                $this->addParticipant($row["active_fi"], new ilTestEvaluationUserData($this->getTest()->getPassScoring()));

                $this->getParticipant($row["active_fi"])->setName(
                    $this->getTest()->buildName($row["usr_id"], $row["firstname"], $row["lastname"], $row["title"])
                );

                $this->getParticipant($row["active_fi"])->setLogin($row["login"]);

                $this->getParticipant($row["active_fi"])->setUserID($row["usr_id"]);

                $this->getParticipant($row["active_fi"])->setSubmitted($row['submitted']);

                $this->getParticipant($row["active_fi"])->setLastFinishedPass($row['last_finished_pass']);
            }

            if (!is_object($this->getParticipant($row["active_fi"])->getPass($row["pass"]))) {
                $pass = new ilTestEvaluationPassData();
                $pass->setPass($row["pass"]);
                $this->getParticipant($row["active_fi"])->addPass($row["pass"], $pass);
            }

            $this->getParticipant($row["active_fi"])->getPass($row["pass"])->setReachedPoints((float) $row["points"]);
            $this->getParticipant($row["active_fi"])->getPass($row["pass"])->setObligationsAnswered($row["obligations_answered"]);

            if ($row["questioncount"] == 0) {
                $data = ilObjTest::_getQuestionCountAndPointsForPassOfParticipant($row['active_fi'], $row['pass']);
                $this->getParticipant($row["active_fi"])->getPass($row["pass"])->setMaxPoints($data['points']);
                $this->getParticipant($row["active_fi"])->getPass($row["pass"])->setQuestionCount($data['count']);
            } else {
                $this->getParticipant($row["active_fi"])->getPass($row["pass"])->setMaxPoints($row["maxpoints"]);
                $this->getParticipant($row["active_fi"])->getPass($row["pass"])->setQuestionCount($row["questioncount"]);
            }

            $this->getParticipant($row["active_fi"])->getPass($row["pass"])->setNrOfAnsweredQuestions($row["answeredquestions"]);
            $this->getParticipant($row["active_fi"])->getPass($row["pass"])->setWorkingTime($row["workingtime"]);
            $this->getParticipant($row["active_fi"])->getPass($row["pass"])->setExamId((string) $row["exam_id"]);

            $this->getParticipant($row['active_fi'])->getPass($row['pass'])->setRequestedHintsCount($row['hint_count']);
            $this->getParticipant($row['active_fi'])->getPass($row['pass'])->setDeductedHintPoints($row['hint_points']);
        }
    }

    public function getTest(): ilObjTest
    {
        return $this->test;
    }

    public function setTest($test)
    {
        $this->test = &$test;
    }

    public function setDatasets($datasets)
    {
        $this->datasets = $datasets;
    }

    public function getDatasets(): int
    {
        return $this->datasets;
    }

    public function addQuestionTitle($question_id, $question_title)
    {
        $this->questionTitles[$question_id] = $question_title;
    }

    public function getQuestionTitles(): array
    {
        return $this->questionTitles;
    }

    public function getQuestionTitle($question_id)
    {
        if (array_key_exists($question_id, $this->questionTitles)) {
            return $this->questionTitles[$question_id];
        } else {
            return "";
        }
    }

    public function calculateStatistics()
    {
        include_once "./Modules/Test/classes/class.ilTestStatistics.php";
        $this->statistics = new ilTestStatistics($this);
    }

    public function getTotalFinishedParticipants(): int
    {
        $finishedParticipants = 0;

        foreach ($this->participants as $active_id => $participant) {
            if (!$participant->isSubmitted()) {
                continue;
            }

            $finishedParticipants++;
        }

        return $finishedParticipants;
    }

    public function getParticipants(): array
    {
        if (is_array($this->arrFilter) && count($this->arrFilter) > 0) {
            $filtered_participants = [];
            $courseids = [];
            $groupids = [];

            if (array_key_exists('group', $this->arrFilter)) {
                $ids = ilObject::_getIdsForTitle($this->arrFilter['group'], 'grp', true);
                $groupids = array_merge($groupids, $ids);
            }
            if (array_key_exists('course', $this->arrFilter)) {
                $ids = ilObject::_getIdsForTitle($this->arrFilter['course'], 'crs', true);
                $courseids = array_merge($courseids, $ids);
            }
            foreach ($this->participants as $active_id => $participant) {
                $remove = false;
                if (array_key_exists('name', $this->arrFilter)) {
                    if (!(strpos(strtolower($participant->getName()), strtolower((string) $this->arrFilter['name'])) !== false)) {
                        $remove = true;
                    }
                }
                if (!$remove) {
                    if (array_key_exists('group', $this->arrFilter)) {
                        $groups = ilParticipants::_getMembershipByType($participant->getUserID(), ["grp"]);
                        $foundfilter = false;
                        if (count(array_intersect($groupids, $groups))) {
                            $foundfilter = true;
                        }
                        if (!$foundfilter) {
                            $remove = true;
                        }
                    }
                }
                if (!$remove) {
                    if (array_key_exists('course', $this->arrFilter)) {
                        $courses = ilParticipants::_getMembershipByType($participant->getUserID(), ["crs"]);
                        $foundfilter = false;
                        if (count(array_intersect($courseids, $courses))) {
                            $foundfilter = true;
                        }
                        if (!$foundfilter) {
                            $remove = true;
                        }
                    }
                }
                if (!$remove) {
                    if (array_key_exists('active_id', $this->arrFilter)) {
                        if ($active_id != $this->arrFilter['active_id']) {
                            $remove = true;
                        }
                    }
                }
                if (!$remove) {
                    $filtered_participants[$active_id] = $participant;
                }
            }
            return $filtered_participants;
        } else {
            return $this->participants;
        }
    }

    public function resetFilter()
    {
        $this->arrFilter = array();
    }

    /*
    * Set an output filter for getParticipants
    *
    * @param string $by name, course, group
    * @param string $text Filter text
    */
    public function setFilter($by, $text)
    {
        $this->arrFilter = array($by => $text);
    }

    /*
    * Set an output filter for getParticipants
    *
    * @param array $arrFilter filter values
    */
    public function setFilterArray($arrFilter)
    {
        $this->arrFilter = $arrFilter;
    }

    public function addParticipant($active_id, $participant)
    {
        $this->participants[$active_id] = $participant;
    }

    /**
     * @param integer $active_id
     * @return ilTestEvaluationUserData
     */
    public function getParticipant($active_id): ilTestEvaluationUserData
    {
        return $this->participants[$active_id];
    }

    public function participantExists($active_id): bool
    {
        return array_key_exists($active_id, $this->participants);
    }

    public function removeParticipant($active_id)
    {
        unset($this->participants[$active_id]);
    }

    public function getStatistics(): object
    {
        return $this->statistics;
    }

    public function getParticipantIds(): array
    {
        return array_keys($this->participants);
    }
} // END ilTestEvaluationData
