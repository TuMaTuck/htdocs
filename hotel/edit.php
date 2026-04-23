<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Reservation</title>
    <link type="text/css" rel="stylesheet" href="media/layout.css" />    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        form { background: white; max-width: 400px; margin: 20px auto; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 0; }
        div { margin-bottom: 10px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .space { margin-top: 20px; }
        input[type="submit"] { background: #007bff; color: white; border: none; padding: 10px 20px; cursor: pointer; width: auto; }
        input[type="submit"]:hover { background: #0069d9; }
        .delete-btn { background: #dc3545; margin-left: 10px; }
        .delete-btn:hover { background: #c82333; }
        a { margin-left: 10px; color: #6c757d; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php
        require_once '_db.php';
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            die("Invalid reservation ID");
        }
        
        // Отримуємо дані бронювання
        $stmt = $db->prepare("SELECT * FROM reservations WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reservation) {
            die("Reservation not found");
        }
        
        // Отримуємо список кімнат
        $rooms = $db->query('SELECT * FROM rooms ORDER BY name');
        
        // Статуси бронювань
        $statusOptions = ["New", "Confirmed", "Arrived", "CheckedOut", "Expired"];
        
        // Варіанти оплати
        $paidOptions = [0, 25, 50, 75, 100];
    ?>
    <form id="f" action="backend_update.php" method="POST" style="padding:20px;">
        <h1>Редагування бронювання</h1>
        
        <input type="hidden" id="id" name="id" value="<?php echo $reservation['id']; ?>" />
        
        <div>Ім'я клієнта: *</div>
        <div><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($reservation['name']); ?>" required /></div>
        
        <div>Дата заїзду: *</div>
        <div><input type="datetime-local" id="start" name="start" value="<?php echo date('Y-m-d\TH:i', strtotime($reservation['start'])); ?>" required /></div>
        
        <div>Дата виїзду: *</div>
        <div><input type="datetime-local" id="end" name="end" value="<?php echo date('Y-m-d\TH:i', strtotime($reservation['end'])); ?>" required /></div>
        
        <div>Кімната: *</div>
        <div>
            <select id="room" name="room" required>
                <?php 
                    foreach ($rooms as $room) {
                        $selected = ($reservation['room_id'] == $room['id']) ? ' selected="selected"' : '';
                        $id = $room['id'];
                        $name = htmlspecialchars($room['name']);
                        $capacity = $room['capacity'];
                        print "<option value='$id'$selected>$name ($capacity місць)</option>";
                    }
                ?>
            </select>
        </div>
        
        <div>Статус бронювання:</div>
        <div>
            <select id="status" name="status">
                <?php 
                    foreach ($statusOptions as $option) {
                        $selected = ($option == $reservation['status']) ? ' selected="selected"' : '';
                        print "<option value='$option'$selected>$option</option>";
                    }
                ?>
            </select>                
        </div>
        
        <div>Оплата (%):</div>
        <div>
            <select id="paid" name="paid">
                <?php 
                    foreach ($paidOptions as $option) {
                        $selected = ($option == $reservation['paid']) ? ' selected="selected"' : '';
                        print "<option value='$option'$selected>$option%</option>";
                    }
                ?>
            </select>
        </div>
        
        <div class="space">
            <input type="submit" value="Зберегти" />
            <input type="button" id="deleteBtn" value="Видалити" class="delete-btn" />
            <a href="javascript:window.close();">Скасувати</a>
        </div>
    </form>
    
    <script>
        $('#f').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.result === 'OK') {
                        window.opener.location.reload();
                        window.close();
                    } else {
                        alert('Помилка: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Помилка сервера: ' + xhr.responseText);
                }
            });
        });
        
        $('#deleteBtn').on('click', function() {
            if (confirm('Ви впевнені, що хочете видалити це бронювання?')) {
                const id = $('#id').val();
                $.ajax({
                    url: 'backend_delete.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.result === 'OK') {
                            window.opener.location.reload();
                            window.close();
                        } else {
                            alert('Помилка: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        alert('Помилка сервера: ' + xhr.responseText);
                    }
                });
            }
        });
    </script>
</body>
</html>