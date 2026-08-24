<?php

require_once __DIR__ ."/../Entity/CommentsHistory.php";
require_once __DIR__.'/GenericRepository.php';

class CommentsHistoryRepository extends GenericRepository {
    public function __construct(Database $database) {
        parent::__construct($database, CommentsHistory::class, 'CommentsHistory');
    }
}