<?php
require __DIR__ . "/../../vendor/autoload.php";
require __DIR__ . "/../Database/DatabaseFactory.php";
require __DIR__ . "/ItemRepairController.php";
header("Content-Type: application/json; charset=utf-8");
$tmcId = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
if ($tmcId <= 0) {
  echo json_encode(["success"=>false,"message"=>"Укажите id ТМЦ"], JSON_UNESCAPED_UNICODE);
  exit;
}
try {
  DatabaseFactory::setConfig();
  $ok = (new ItemRepairController())->returnFromBasket($tmcId);
  echo json_encode([
    "success"=>$ok,
    "message"=>$ok ? "ТМЦ {$tmcId} возвращён из корзины" : "Не удалось вернуть ТМЦ {$tmcId}",
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(["success"=>false,"message"=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
