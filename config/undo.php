<?php

return [
    'window_minutes' => env('UNDO_WINDOW_MINUTES', 10),

    'handlers' => [
        \App\Services\Undo\Handlers\StockMovementUndoHandler::class,
        \App\Services\Undo\Handlers\WorkOrderStatusUndoHandler::class,
        \App\Services\Undo\Handlers\WorkOrderPartUndoHandler::class,
    ],
];
