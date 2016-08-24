<?php
/**
 * A simple carrier for mass info-settings.
 * Could have used an associative array as well...
 */

class ilManualAssessmentInfoSettings {
	protected $id;
	protected $contanct;
	protected $responsibility;
	protected $phone;
	protected $mails;
	protected $consultation_hours;

	public function __construct(
			ilObjManualAssessment $mass
			, $contanct = null
			, $responsibility = null
			, $phone = null
			, $mails = null
			, $consultation_hours = null) {

		$this->contact = $contanct;
		$this->responsibility = $responsibility;
		$this->phone = $phone;
		$this->mails = $mails;
		$this->consultation = $consultation_hours;
	}

	public function contact() {
		return $this->contact;
	}

	public function responsibility() {
		return $this->responsibility;
	}

	public function phone() {
		return $this->phone;
	}

	public function mails() {
		return $this->mails;
	}

	public function consultationHours() {
		return $this->consultation_hours;
	}

	public function setContact($contact) {
		assert('is_string($contact) || $contact === null');
		$this->contact = $contact;
		return $this;
	}

	public function setResponsibility($responsibility) {
		assert('is_string($responsibility) || $responsibility === null');
		$this->responsibility = $responsibility;
		return $this;
	}

	public function setPhone($phone) {
		assert('is_string($phone) || $phone === null');
		$this->phone = $phone;
		return $this;
	}

	public function setMails($mails) {
		assert('is_string($mails) || $mails === null');
		$this->mails = $mails;
		return $this;
	}

	public function setConsultationHours($consultation_hours) {
		assert('is_string($consultation_hours) || $consultation_hours === null');
		$this->consultation_hours = $consultation_hours;
		return $this;
	}
}