<?php
// Підключаємо файл з підключенням до бази даних
require_once '_db.php';

// У файлі _db.php змінна називається $db (не $pdo)
// Перевіряємо, чи з'єднання існує
if (!$db) {
    die("Помилка підключення до бази даних");
}

// Отримання списку кімнат
$stmt = $db->query("SELECT * FROM rooms ORDER BY id");
$rooms = $stmt->fetchAll();

// Отримання бронювань
$stmt = $db->query("
    SELECT r.*, rooms.name as room_name 
    FROM reservations r 
    JOIN rooms ON r.room_id = rooms.id 
    ORDER BY start
");
$reservations = $stmt->fetchAll();

// Передача даних у JavaScript
$roomsJson = json_encode($rooms);
$reservationsJson = json_encode($reservations);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Готель - Система бронювання кімнат</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="style.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DayPilot Scheduler -->
    <script src="https://cdn.daypilot.org/daypilot-all.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e9ecef;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .label {
            font-size: 13px;
            color: #495057;
            font-weight: 500;
        }
        
        .filter-select {
            padding: 6px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background: #fff;
            font-size: 13px;
            cursor: pointer;
        }
        
        .demo-badge {
            position: relative;
            float: right;
            margin: 10px 20px;
            padding: 4px 12px;
            background: #ffc107;
            color: #856404;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
            display: inline-block;
        }
        
        #scheduler {
            width: 100%;
            min-height: 600px;
        }
        
        .status-dirty { color: #dc3545; font-weight: bold; }
        .status-cleaning { color: #fd7e14; font-weight: bold; }
        .status-ready { color: #28a745; font-weight: bold; }
        
        .event-item { padding: 4px; font-size: 11px; line-height: 1.4; }
        .event-name { font-weight: bold; font-size: 12px; margin-bottom: 2px; }
        .event-dates { font-size: 10px; opacity: 0.9; }
        .event-status { font-size: 10px; margin-top: 2px; opacity: 0.8; }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: #fff;
            margin: 5% auto;
            padding: 25px;
            width: 90%;
            max-width: 450px;
            border-radius: 12px;
            position: relative;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
        }
        
        .modal-content h3 { margin-bottom: 20px; }
        .modal-content label { display: block; margin: 12px 0 4px; font-weight: 500; }
        .modal-content input, .modal-content select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
        }
        
        .modal-buttons { display: flex; gap: 10px; margin-top: 20px; }
        .btn-save { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; flex: 1; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; flex: 1; }
        .btn-cancel { background: #6c757d; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; }
        
        footer {
            background: #2d3748;
            color: #a0aec0;
            text-align: center;
            padding: 15px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="toolbar">
            <div class="toolbar-left">
                <span class="label">Show rooms:</span>
                <select id="roomFilter" class="filter-select">
                    <option value="all">All</option>
                    <?php foreach($rooms as $room): ?>
                        <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="toolbar-right">
                <span class="label">Time range:</span>
                <select id="timeRange" class="filter-select">
                    <option value="month">Month ▼</option>
                    <option value="week">Week</option>
                    <option value="day">Day</option>
                </select>
                <span class="label">Auto Cell Width</span>
                <input type="checkbox" id="autoCellWidth" checked>
            </div>
        </div>
        
        <div class="demo-badge">DEMO</div>
        <div id="scheduler"></div>
    </div>
    
    <footer>
        <address>© Автор лабораторної роботи: студент спеціальності G7 <<Робототехніка>> Мельников Дмитро Олександрович</address>
    </footer>
    
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Редагування бронювання</h3>
            <form id="bookingForm">
                <input type="hidden" id="bookingId">
                <label>Ім'я клієнта:</label>
                <input type="text" id="bookingName" required>
                <label>Кімната:</label>
                <select id="bookingRoom" required></select>
                <label>Дата заїзду:</label>
                <input type="date" id="bookingStart" required>
                <label>Дата виїзду:</label>
                <input type="date" id="bookingEnd" required>
                <label>Статус:</label>
                <select id="bookingStatus">
                    <option value="new">New</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="arrived">Arrived</option>
                    <option value="checkedout">Checked out</option>
                </select>
                <label>Оплата (%):</label>
                <input type="number" id="bookingPaid" min="0" max="100" value="0">
                <div class="modal-buttons">
                    <button type="submit" class="btn-save">Зберегти</button>
                    <button type="button" id="deleteBtn" class="btn-delete">Видалити</button>
                    <button type="button" id="cancelBtn" class="btn-cancel">Скасувати</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const rooms = <?= $roomsJson ?>;
        const reservations = <?= $reservationsJson ?>;
        
        let scheduler = null;
        
        function getStatusColor(status) {
            const colors = {
                'New': '#3498db',
                'Confirmed': '#2ecc71',
                'Arrived': '#9b59b6',
                'CheckedOut': '#e67e22',
                'Expired': '#95a5a6'
            };
            return colors[status] || '#3498db';
        }
        
        function initScheduler() {
            scheduler = new DayPilot.Scheduler("scheduler");
            scheduler.viewType = "Days";
            scheduler.timeRange.start = new Date(2026, 3, 1);
            scheduler.timeRange.end = new Date(2026, 3, 16);
            scheduler.cellWidth = 80;
            scheduler.cellHeaderFormat = "d";
            scheduler.rowHeaderWidth = 200;
            
            scheduler.resources = rooms.map(room => ({
                id: room.id.toString(),
                name: room.name,
                capacity: room.capacity,
                status: room.status
            }));
            
            scheduler.events = reservations.map(res => ({
                id: res.id.toString(),
                resource: res.room_id.toString(),
                start: res.start,
                end: res.end,
                name: res.name,
                status: res.status,
                paid: res.paid,
                backColor: getStatusColor(res.status),
                barColor: getStatusColor(res.status)
            }));
            
            scheduler.columns = [
                { name: "Room", width: 150 },
                { name: "Capacity", width: 80 },
                { name: "Status", width: 100 }
            ];
            
            scheduler.onBeforeRowHeaderRender = function(args) {
                let statusText = '', statusClass = '';
                switch(args.row.data.status) {
                    case 'Dirty': statusText = 'Dirty'; statusClass = 'status-dirty'; break;
                    case 'Cleaning': statusText = 'Cleanup'; statusClass = 'status-cleaning'; break;
                    default: statusText = 'Ready'; statusClass = 'status-ready';
                }
                args.row.columns[0].html = args.row.name;
                args.row.columns[1].html = args.row.data.capacity + ' beds';
                args.row.columns[2].html = `<span class="${statusClass}">${statusText}</span>`;
            };
            
            scheduler.onBeforeEventRender = function(args) {
                args.data.backColor = getStatusColor(args.data.status);
                args.data.barColor = getStatusColor(args.data.status);
                args.data.html = `<div class="event-item">
                    <div class="event-name">${args.data.name}</div>
                    <div class="event-status">${args.data.status} | Paid: ${args.data.paid}%</div>
                </div>`;
            };
            
            scheduler.init();
        }
        
        $(document).ready(function() {
            initScheduler();
            
            $('.close, #cancelBtn').on('click', function() {
                $('#modal').hide();
            });
        });
    </script>
</body>
</html>