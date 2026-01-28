<?php
require_once './team_project/common/connect_cjd102g1.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>更新健康數值</title>
</head>

<body>
    <h2>更新會員體重</h2>
    <?php
        //抓出小明的資料
        $stmt = $pdo->prepare("SELECT full_name, weight FROM Members WHERE member_id = 1");
        $stmt -> execute();
        $user = $stmt->fetch();
    ?>
    <form action="test_handle_update.php" method="post">
        <p>會員名稱</p>
        <?php
            echo htmlspecialchars($user['full_name']);
        ?>

        <input type="hidden" name="id" value="1">

        <label>目前體重</label>
        <input type="number" name="weight" value="<?php echo $user['weight']; ?>" require>

        <br>
        <br>
        <button type="submit">確認更新</button>
    </form>
</body>

</html>