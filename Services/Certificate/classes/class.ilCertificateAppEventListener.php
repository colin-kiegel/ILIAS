<?php
/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilCertificateAppEventListener
 *
 * @author Niels Theen <ntheen@databay.de>
 * @version $Id:$
 *
 * @package Services/Certificate
 */
class ilCertificateAppEventListener implements ilAppEventListener
{
	/**
	 * @inheritdoc
	 */
	public static function handleEvent($a_component, $a_event, $a_params)
	{
		global $DIC;

		$database = $DIC->database();
		$ilObjectDataCache = $DIC['ilObjDataCache'];
		$logger = $DIC->logger()->cert();

		switch($a_component) {
			case 'Services/Tracking':
				switch($a_event) {
					case 'updateStatus':
						self::handleLPUpdate(
							$a_params,
							$DIC->database(),
							$ilObjectDataCache,
							$logger
						);
						break;
				}
			break;

			case 'Services/Certificate':
				switch($a_event) {
					case 'migrateUserCertificate':
						try {
							self::handleNewUserCertificate($a_params, $database, $logger);
						} catch (ilException $exception) {
							$logger->error($exception->getMessage());
						}
						break;
				}
				break;
		}
	}

	/**
	 * @param $a_params
	 * @param ilDBInterface $database
	 * @param ilObjectDataCache $ilObjectDataCache
	 * @param ilLogger $logger
	 * @throws ilException
	 */
	private static function handleLPUpdate(
		$a_params,
		ilDBInterface $database,
		ilObjectDataCache $ilObjectDataCache,
		ilLogger $logger
	) {
		if ($a_params['status'] == ilLPStatus::LP_STATUS_COMPLETED_NUM) {
			/** @var ilObjectDataCache $ilObjectDataCache */

			$certificateQueueRepository = new ilCertificateQueueRepository($database, $logger);
			$certificateClassMap = new ilCertificateTypeClassMap();
			$templateRepository = new ilCertificateTemplateRepository($database, $logger);

			$objectId = $a_params['obj_id'];
			$userId = $a_params['usr_id'];

			$type = $ilObjectDataCache->lookupType($objectId);

			$template = $templateRepository->fetchCurrentlyActiveCertificate($objectId);

			if (true === $template->isCurrentlyActive()) {
				if ($certificateClassMap->typeExistsInMap($type)) {
					$className = $certificateClassMap->getPlaceHolderClassNameByType($type);

					$entry = new ilCertificateQueueEntry(
						$objectId,
						$userId,
						$className,
						ilCronConstants::IN_PROGRESS,
						$template->getId(),
						time()
					);

					$certificateQueueRepository->addToQueue($entry);
				}
			}

			foreach (ilObject::_getAllReferences($objectId) as $refId) {
				$templateRepository = new ilCertificateTemplateRepository($database, $logger);
				$progressEvaluation = new ilCertificateCourseLearningProgressEvaluation($templateRepository);

				$completedCourses = $progressEvaluation->evaluate($refId, $userId);
				foreach ($completedCourses as $courseObjId) {
					$courseTemplate = $templateRepository->fetchCurrentlyActiveCertificate($courseObjId);

					if (true === $courseTemplate->isCurrentlyActive()) {
						$type = $ilObjectDataCache->lookupType($courseObjId);

						$className = $certificateClassMap->getPlaceHolderClassNameByType($type);

						$entry = new ilCertificateQueueEntry(
							$courseObjId,
							$userId,
							$className,
							ilCronConstants::IN_PROGRESS,
							time()
						);

						$certificateQueueRepository->addToQueue($entry);
					}
				}
			}
		}
	}

	/**
	 * @param $a_params
	 * @param ilDBInterface $database
	 * @param ilLogger $logger
	 * @return void
	 * @throws ilDatabaseException
	 * @throws ilException
	 */
	private static function handleNewUserCertificate($a_params, ilDBInterface $database, ilLogger $logger)
	{
		$logger->info('Try to create new certificates based on event');

		if (false === array_key_exists('obj_id', $a_params)) {
			return $logger->error('Object ID is not added to the event. Abort.');
		}

		if (false === array_key_exists('user_id', $a_params)) {
			return $logger->error('User ID is not added to the event. Abort.');
		}

		if (false === array_key_exists('background_image_path', $a_params)) {
			return $logger->error('Background Image Path is not added to the event. Abort.');
		}

		if (false === array_key_exists('acquired_timestamp', $a_params)) {
			return $logger->error('Acquired Timestamp is not added to the event. Abort.');
		}

		if (false === array_key_exists('ilias_version', $a_params)) {
			return $logger->error('ILIAS version is not added to the event. Abort.');
		}

		$objectId = $a_params['obj_id'];
		$userId = $a_params['user_id'];
		$backgroundImagePath = $a_params['background_image_path'];
		$acquiredTimestamp = $a_params['acquired_timestamp'];
		$iliasVersion = $a_params['ilias_version'];

		$templateRepository = new ilCertificateTemplateRepository($database, $logger);
		$template = $templateRepository->fetchFirstCreatedTemplate($objectId);

		$userCertificateRepository = new ilUserCertificateRepository($database, $logger);

		$type = ilObject::_lookupType($objectId);

		$classMap = new ilCertificateTypeClassMap();
		$className = $classMap->getPlaceHolderClassNameByType($type);

		$placeholderValuesObject = new $className();
		$placeholderValues = $placeholderValuesObject->getPlaceholderValues($userId, $objectId);

		$replacementService = new ilCertificateValueReplacement();
		$certificateContent = $replacementService->replace(
			$placeholderValues,
			$template->getCertificateContent(),
			$backgroundImagePath
		);


		$user = ilObjectFactory::getInstanceByObjId($userId, false);

		if (!$user || !($user instanceof \ilObjUser)) {
			throw new ilException(sprintf('The given user ID("%s") is not a user', $userId));
		}

		$userCertificate = new ilUserCertificate(
			$template->getId(),
			$objectId,
			$type,
			$userId,
			$user->getFullname(),
			$acquiredTimestamp,
			$certificateContent,
			'',
			null,
			1,
			$iliasVersion,
			true,
			$backgroundImagePath
		);

		$userCertificateRepository->save($userCertificate);
	}
}
