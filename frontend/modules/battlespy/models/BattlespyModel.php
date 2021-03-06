<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

/**
 * Battlespy Model
 *
 * @package Models
 * @subpackage Battlepspy
 */
class BattlespyModel
{
    /**
     * @var \System\Database\DbConnection The stats database connection
     */
    protected $pdo;

    /**
     * BattlespyModel constructor.
     */
    public function __construct()
    {
        // Fetch database connection
        $this->pdo = System\Database::GetConnection('stats');
    }

    /**
     * This method retrieves the battlespy report list
     *
     * @return array
     */
    public function getReportList()
    {
        // Fetch reports
        $query = <<<SQL
SELECT r.*, rh.mapid, rh.round_end AS `timestamp`, s.name AS `server`, mi.name AS `mapname`,
  (SELECT COUNT(id) FROM battlespy_message WHERE `reportid` = r.id) AS `count`
FROM battlespy_report AS r
  LEFT JOIN round_history AS rh ON r.roundid = rh.id
  LEFT JOIN server AS s ON r.serverid = s.id
  LEFT JOIN mapinfo AS mi ON rh.mapid = mi.id
SQL;
        $result = $this->pdo->query($query);
        $reports = [];

        // Add date format
        while ($report = $result->fetch())
        {
            $i = (int)$report['timestamp'];
            $report['date'] = date('F jS, Y g:i A T', $i);
            $reports[] = $report;
        }

        return $reports;
    }

    /**
     * Fetches a battlespy report, and it's messages
     *
     * @param int $id The report id
     *
     * @return array|bool false if the report does not exist, otherwise the
     *  report information in a two-dimensional array.
     */
    public function getReportById($id)
    {
        // Fetch report
        $query = <<<SQL
SELECT r.*, rh.mapid, rh.round_end AS `timestamp`, s.name AS `server`, mi.name AS `mapname`,
FROM battlespy_report AS r
  LEFT JOIN round_history AS rh ON r.roundid = rh.id
  LEFT JOIN server AS s ON r.serverid = s.id
  LEFT JOIN mapinfo AS mi ON rh.mapid = mi.id
WHERE r.id = {$id} LIMIT 1
SQL;

        // Execute query and ensure report exists
        $report = $this->pdo->query($query)->fetch();
        if (empty($report))
            return false;

        // Fetch report messages
        $messages = [];
        $query = "SELECT m.*, p.name FROM battlespy_message AS m JOIN player AS p ON m.pid = p.id WHERE reportid=". $id;
        $results = $this->pdo->query($query);

        // Add css badge text
        while ($row = $results->fetch())
        {
            $message = $row;
            $severity = (int)$row['severity'];

            switch ($severity)
            {
                case 3:
                    $message['badge'] = 'important';
                    break;
                case 2:
                    $message['badge'] = 'warning';
                    break;
                default:
                    $message['badge'] = 'info';
                    break;
            }

            // Add message
            $messages[] = $message;
        }

        return [
            'report' => $report,
            'messages' => $messages
        ];
    }

    /**
     * Deletes a list of BattleSpy reports by id
     *
     * @param int[] $ids A list of report ids to perform the action on
     *
     * @throws Exception thrown if there is an error in the SQL statement
     */
    public function deleteReports($ids)
    {
        $count = count($ids);

        // Prepared statement!
        try
        {
            // Transaction if more than 2 servers
            if ($count > 2)
                $this->pdo->beginTransaction();

            // Prepare statement
            $stmt = $this->pdo->prepare("DELETE FROM battlespy_report WHERE id=:id");
            foreach ($ids as $id)
            {
                // Ignore the all!
                if ($id == 'all') continue;

                // Bind value and run query
                $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Commit?
            if ($count > 2)
                $this->pdo->commit();
        }
        catch (Exception $e)
        {
            // Rollback?
            if ($count > 2)
                $this->pdo->rollBack();

            throw $e;
        }
    }

    /**
     * Deletes a list of BattleSpy report messages by id
     *
     * @param int[] $ids A list of message ids to perform the action on
     *
     * @throws Exception thrown if there is an error in the SQL statement
     */
    public function deleteMessages($ids)
    {
        $count = count($ids);

        // Prepared statement!
        try
        {
            // Transaction if more than 2 servers
            if ($count > 2)
                $this->pdo->beginTransaction();

            // Prepare statement
            $stmt = $this->pdo->prepare("DELETE FROM battlespy_message WHERE id=:id");
            foreach ($ids as $id)
            {
                // Ignore the all!
                if ($id == 'all') continue;

                // Bind value and run query
                $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Commit?
            if ($count > 2)
                $this->pdo->commit();
        }
        catch (Exception $e)
        {
            // Rollback?
            if ($count > 2)
                $this->pdo->rollBack();

            throw $e;
        }
    }

    public function deleteReportById($id)
    {

    }

    public function deleteMessageById($id)
    {

    }
}