<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Reservation</title>
    <link type="text/css" rel="stylesheet" href="media/layout.css" />    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        form { background: white; max-width: 400px; margin: 20px auto; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 0; }
        div { margin-bottom: 10px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .space { margin-top: 20px; }
        input[type="submit"] { background: #28a745; color: white; border: none; padding: 10px 20px; cursor: pointer; width: auto; }
        input[type="submit"]:hover { background: #218838; }
        a { margin-left: 10px; color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .error { color: red; font-size: 12px; margin-top: 5px; }
    </style>
</head>
<body>
    <?php
        require_once '_db.php';
        
        $rooms = $db->query('SELECT * FROM rooms ORDER BY name');
        
        // Отримуємо та форматуємо дати
        $start = isset($_GET['start']) ? date('Y-m-d H:i:s', strtotime($_GET['start'])) : date('Y-m-d H:i:s');
        $end = isset($_GET['end']) ? date('Y-m-d H:i:s', strtotime($_GET['end'])) : date('Y-m-d H:i:s', strtotime('+1 day'));
        $resource = isset($_GET['resource']) ? (int)$_GET['resource'] : 1;
    ?>
    <form id="f" action="backend_create.php" method="POST" style="padding:20px;">
        <h1>Нове бронювання</h1>
        
        <div>Ім'я клієнта: *</div>
        <div><input type="text" id="name" name="name" value="" required /></div>
        
        <div>Дата заїзду: *</div>
        <div><input type="datetime-local" id="start" name="start" value="<?php echo date('Y-m-d\TH:i', strtotime($start)); ?>" required /></div>
        
        <div>Дата виїзду: *</div>
        <div><input type="datetime-local" id="end" name="end" value="<?php echo date('Y-m-d\TH:i', strtotime($end)); ?>" required /></div>
        
        <div>Кімната: *</div>
        <div>
            <select id="room" name="room" required>
                <?php 
                    foreach ($rooms as $room) {
                        $selected = ($resource == $room['id']) ? ' selected="selected"' : '';
                        $id = $room['id'];
                        $name = htmlspecialchars($room['name']);
                        $capacity = $room['capacity'];
                        print "<option value='$id'$selected>$name ($capacity місць)</option>";
                    }
                ?>
            </select>
        </div>
        
        <div class="space">
            <input type="submit" value="Зберегти" /> 
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
    </script>
</body>
</html>