<?php


class ilCertificateGUIFactory
{
	/**
	 * @var
	 */
	private $dic;

	/**
	 * @param \ILIAS\DI\Container|null $dic
	 */
	public function __construct(\ILIAS\DI\Container $dic = null)
	{
		if (null === $dic) {
			global $DIC;
			$dic = $DIC;
		}
		$this->dic = $dic;
	}

	/**
	 * @param ilObject $object
	 * @return ilCertificateGUI
	 * @throws ilException
	 */
	public function create(\ilObject $object)
	{
		global $DIC;

		$type = $object->getType();
		$objectId = $object->getId();

		$logger = ilLoggerFactory::getLogger('cert');

		$templateRepository = new ilCertificateTemplateRepository($this->dic->database(), $logger);
		$deleteAction = new ilCertificateTemplateDeleteAction($templateRepository);

		switch ($type) {
			case 'tst':
				$placeholderDescriptionObject = new ilTestPlaceholderDescription();
				$placeholderValuesObject = new ilTestPlaceHolderValues();
				$adapter = new ilTestCertificateAdapter($object);

				$certificatePath = ilCertificatePathConstants::TEST_PATH . $objectId . '/';

				$formFactory = new ilCertificateSettingsTestFormRepository(
					$objectId,
					$certificatePath,
					$object,
					$DIC->language(),
					$DIC->ui()->mainTemplate(),
					$DIC->ctrl(),
					$DIC->access(),
					$DIC->toolbar(),
					$placeholderDescriptionObject
				);

				$certificatePath = ilCertificatePathConstants::TEST_PATH . $objectId . '/';

				$deleteAction = new ilCertificateTestTemplateDeleteAction($deleteAction);

				break;
			case 'crs':
				$adapter = new ilCourseCertificateAdapter($object);
				$placeholderDescriptionObject = new ilCoursePlaceholderDescription();
				$placeholderValuesObject = new ilCoursePlaceholderValues();

				$certificatePath = ilCertificatePathConstants::COURSE_PATH . $objectId . '/';

				$formFactory = new ilCertificateSettingsFormRepository(
					$object->getId(),
					$certificatePath,
					$DIC->language(),
					$DIC->ui()->mainTemplate(),
					$DIC->ctrl(),
					$DIC->access(),
					$DIC->toolbar(),
					$placeholderDescriptionObject
				);

				$certificatePath = ilCertificatePathConstants::COURSE_PATH . $objectId . '/';
				break;
			case 'exc':
				$adapter = new ilExerciseCertificateAdapter($object);
				$placeholderDescriptionObject = new ilExercisePlaceholderDescription();
				$placeholderValuesObject = new ilExercisePlaceHolderValues();

				$certificatePath = ilCertificatePathConstants::EXERCISE_PATH . $objectId . '/';

				$formFactory = new ilCertificateSettingsExerciseRepository(
					$object,
					$certificatePath,
					$DIC->language(),
					$DIC->ui()->mainTemplate(),
					$DIC->ctrl(),
					$DIC->access(),
					$DIC->toolbar(),
					$placeholderDescriptionObject
				);

				$certificatePath = ilCertificatePathConstants::EXERCISE_PATH . $objectId . '/';
				break;
			case 'scorm':
				$adapter = new ilSCORMCertificateAdapter($object);
				$placeholderDescriptionObject = new ilScormPlaceholderDescription();
				$placeholderValuesObject = new ilScormPlaceholderValues();

				$certificatePath = ilCertificatePathConstants::SCORM_PATH . $objectId . '/';

				$formFactory = new ilCertificateSettingsScormFormRepository(
					$objectId,
					$certificatePath,
					$DIC->language(),
					$DIC->ui()->mainTemplate(),
					$DIC->ctrl(),
					$DIC->access(),
					$DIC->toolbar(),
					$placeholderDescriptionObject
				);

				$certificatePath = ilCertificatePathConstants::SCORM_PATH . $objectId . '/';

				break;
			default:
				throw new ilException(sprintf('The type "%s" is currently not defined for certificates', $type));
				break;
		}

		$gui = new ilCertificateGUI(
			$adapter,
			$placeholderDescriptionObject,
			$placeholderValuesObject,
			$objectId,
			$certificatePath,
			$formFactory,
			$deleteAction
		);

		return $gui;
	}
}
