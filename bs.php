<?php
// Генерация DOT-кода для схемы
$dot = <<<DOT
digraph {
    node [shape=box, style="rounded", fontname="Arial"];
    edge [fontname="Arial"];
    
    start [label="Начало"];
    condition [label="Условие?", shape=diamond];
    action1 [label="Действие 1"];
    action2 [label="Действие 2"];
    end [label="Конец"];

    start -> condition;
    condition -> action1 [label="Да"];
    condition -> action2 [label="Нет"];
    action1 -> end;
    action2 -> end;
}
DOT;

// Отправка запроса в онлайн-сервис GraphvizOnline
$imageUrl = "https://graphviz.chapy.dev/svg?" . rawurlencode($dot);

// Вывод изображения в браузере
echo '<img src="' . htmlspecialchars($imageUrl) . '" alt="Блок-схема">';
?>