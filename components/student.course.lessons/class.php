<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use Kdelo\StudentGroup;

class CourseOptionsSetter extends CBitrixComponent
{
    public function setBaseCourseOptions(StudentGroup $GROUP) : void
    {
        $this->arResult['COURSE']['NAME'] =  $GROUP->course->name;
        $this->arResult['COURSE']['FINISH_DATE'] =  $GROUP->dateFinish;
        $this->arResult['COURSE']['START_DATE'] =  $GROUP->dateStart;
    }
    
    public function setTimeOptions(StudentGroup $GROUP, string $returnOption = null) : ?int
    {
        $this->arResult['TIME_LEFT'] = $this->arResult['IS_WAITING'] = false;
        
        $timeLeft =  $GROUP->dateFinishTimestamp - time();
        $timeToStart =  $GROUP->dateStartTimestamp - time();

        if($timeLeft > 0){
            $this->arResult['TIME_LEFT'] = true;
            $this->arResult['DAYS_LEFT'] = intdiv($timeLeft, 86400);
        }

        if($timeToStart > 0)
            $this->arResult['IS_WAITING'] = true;
     
        if($returnOption === 'return_timeToStart')
            return $timeToStart;
        elseif($returnOption === 'return_timeLeft')
            return $timeLeft;
        else
            return null;
    }

    public function setFormOptions(StudentGroup $GROUP, $studentId) : void
    {
        $this->arResult['IS_FORM_ACTIVE'] = $GROUP->course->setForm()->isFormActive && in_array($studentId, $GROUP->getOpenFormForStudents());
    }
}
?>