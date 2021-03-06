<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System;

use System\Database\DbConnection;

/**
 * This class represents a Backend Award with a series of criteria's that
 * can be tested against a player
 *
 * @package System
 */
class BackendAward
{
    /**
     * @var int
     */
    public $awardId = 0;

    /**
     * @var AwardCriteria[]
     */
    protected $awardCriteria = array();

    /**
     * BackendAward constructor.
     *
     * @param $awardId
     * @param $criteria
     */
    public function __construct($awardId, $criteria)
    {
        $this->awardId = (int)$awardId;
        $this->awardCriteria = $criteria;
    }

    /**
     * Determines whether or not a player has met all of the required criteria to
     * earn this backend award.
     *
     * This method does properly allow multiple awarding of backend medals
     *
     * @param Player $player The player to run the criteria against
     * @param DbConnection $connection Stats database connection
     * @param int $awardCount [Reference Variable] Returns the amount of times the Award has
     *  been awarded to the player.
     *
     * @return bool true if the player has met the criteria to earn this award, or false
     */
    public function criteriaMet(Player $player, DbConnection $connection, &$awardCount)
    {
        // Get the award count (or level for badges) for this award
        $query = "SELECT COUNT(id) FROM player_award WHERE pid=%d AND id=%d";
        $result = $connection->query(sprintf($query, $player->pid, $this->awardId));
        $awardCount = (int) $result->fetchColumn();
        $isRibbon = ($this->awardId > 3000000);

        // Can only receive ribbons once in a lifetime, so return false if we have it already
        if ($isRibbon && $awardCount > 0)
            return false;

        // Loop through each criteria and see if we have met the criteria
        foreach ($this->awardCriteria as $criteria)
        {
            // Build the where statement for backend medals
            $where = str_replace('###', $awardCount, $criteria->where);

            /** @noinspection SqlResolve */
            $query = vsprintf("SELECT %s FROM `%s` WHERE `pid`=%d AND %s LIMIT 1", [
                $criteria->field,
                $criteria->table,
                $player->pid,
                $where
            ]);

            $row = $connection->query($query)->fetch();
            if (empty($row) || !$criteria->checkCriteria($row, $awardCount))
                return false;
        }

        return true;
    }
}