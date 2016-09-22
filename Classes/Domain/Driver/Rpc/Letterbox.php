<?php
namespace In2code\In2publishCore\Domain\Driver\Rpc;

/***************************************************************
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Letterbox
 */
class Letterbox
{
    const TABLE = 'tx_in2code_in2publish_envelope';

    /**
     * @var ContextService
     */
    protected $contextService = null;

    /**
     * Letterbox constructor.
     */
    public function __construct()
    {
        $this->contextService = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Service\\Context\\ContextService'
        );
    }

    /**
     * @param Envelope $envelope
     * @return bool|int false for errors, int for successful sent envelopes and true for updated envelopes (yes, ugly)
     */
    public function sendEnvelope(Envelope $envelope)
    {
        if ($this->contextService->isLocal()) {
            $database = DatabaseUtility::buildForeignDatabaseConnection();
        } else {
            $database = DatabaseUtility::buildLocalDatabaseConnection();
        }

        $uid = (int)$envelope->getUid();

        if (0 === $uid || 0 === $database->exec_SELECTcountRows('uid', static::TABLE, 'uid=' . $uid)) {
            if (true === $database->exec_INSERTquery(static::TABLE, $envelope->toArray())) {
                $uid = $database->sql_insert_id();
                $envelope->setUid($uid);
                return $uid;
            }
        } else {
            return (bool)$database->exec_UPDATEquery(static::TABLE, 'uid=' . $uid, $envelope->toArray());
        }
        return false;
    }

    /**
     * @param int $uid
     * @return bool|Envelope
     */
    public function receiveEnvelope($uid)
    {
        if ($this->contextService->isForeign()) {
            $database = DatabaseUtility::buildLocalDatabaseConnection();
        } else {
            $database = DatabaseUtility::buildForeignDatabaseConnection();
        }

        $envelopeData = $database->exec_SELECTgetSingleRow(
            'command,request,response,uid',
            static::TABLE,
            'uid=' . (int)$uid
        );

        if (is_array($envelopeData)) {
            $envelope = Envelope::fromArray($envelopeData);
        } else {
            $envelope = false;
        }
        return $envelope;
    }
}
