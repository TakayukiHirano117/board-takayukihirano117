<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>簡易掲示板アプリ</title>
</head>
<body>
<?php
// データベースに接続
$dsn = 'データベース名';
$user = 'ユーザー名';
$password = 'パスワード';
$pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

// $dropTableSql = 'DROP TABLE IF EXISTS board;';
// $result = $pdo->query($dropTableSql);

// テーブルが無かったら作成
$createTableSql = <<<EOT
CREATE TABLE  IF NOT EXISTS board (
    id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32),
    comment TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    post_pass VARCHAR(255) NOT NULL
) DEFAULT CHARACTER SET=utf8mb4;
EOT;

$result = $pdo->query($createTableSql);

// require_once __DIR__ . "/lib/escape.php";

// 新規投稿時の処理 リロードしたときに二重投稿されるのを直す
if(!empty($_POST["name"]) && !empty($_POST["comment"]) && !empty($_POST["post_pass"]) && empty($_POST["flag"])) {
    $name = $_POST["name"];
    $comment = $_POST["comment"];
    // $date = date("Y/m/d H:i:s");
    $post_pass = password_hash($_POST["post_pass"], PASSWORD_DEFAULT);

    $sql = $pdo->prepare("INSERT INTO board (name, comment, post_pass) VALUES (:name, :comment, :post_pass)");
    $sql -> bindParam(':name', $name, PDO::PARAM_STR);
    $sql -> bindParam(':comment', $comment, PDO::PARAM_STR);
    $sql -> bindParam(':post_pass', $post_pass, PDO::PARAM_STR);
    $sql -> execute();
    
// 削除機能の実装
} elseif (!empty($_POST["num_delete"]) && !empty($_POST["delete_pass"])) {
    $num_delete = $_POST["num_delete"];
    $delete_pass = $_POST["delete_pass"];

    // データベースから投稿番号とパスワードを取ってくる、(パスワードは照合する)
    $selectRecordSql = "SELECT * FROM board";
    $result = $pdo -> query($selectRecordSql);
    $results = $result -> fetchAll();

    foreach($results as $row) {
        if($row["id"] === $num_delete && password_verify($delete_pass, $row['post_pass']) === true) {
        $deleteRecordSql = "delete from board where id=:id";
        $result = $pdo->prepare($deleteRecordSql);
        $result->bindParam(":id", $num_delete, PDO::PARAM_INT);
        $result->execute();
    }
}

// 編集機能の実装
} elseif (!empty($_POST["num_edit"]) && !empty($_POST["edit_pass"])) {
    $num_edit = $_POST["num_edit"];
    $edit_pass = $_POST["edit_pass"];

    $selectRecordSql = "SELECT * FROM board";
    $result = $pdo -> query($selectRecordSql);
    $results = $result -> fetchAll();

    foreach($results as $row) {
        if($row["id"] === $num_edit && password_verify($edit_pass, $row["post_pass"]) === true) {
            $edit_number = $row["id"];
            $edit_name = $row["name"];
            $edit_comment = $row["comment"];
            break;
        }
    }
// 編集フォームで取得したデータを投稿ドームに表示、再度投稿すると
// 変更が反映される
} elseif (!empty($_POST["flag"]) && !empty($_POST["name"]) && !empty($_POST["comment"]) && !empty($_POST["post_pass"])) {
    $update_number = $_POST["flag"];
    $update_name = $_POST["name"];
    $update_comment = $_POST["comment"];
    $update_pass = $_POST["post_pass"];
    
    $updateRecordSql = "UPDATE board SET name=:name, comment=:comment, post_pass=:post_pass WHERE id=:id";
    $result = $pdo->prepare($updateRecordSql);
    $result->bindParam(":name", $update_name, PDO::PARAM_STR);
    $result->bindParam(":comment", $update_comment, PDO::PARAM_STR);
    $result->bindParam(":id", $update_number, PDO::PARAM_INT);
    $result->bindParam(":post_pass", $update_pass, PDO::PARAM_STR);
    $result->execute();
}

?>

<p>【投稿送信フォーム】</p>
<form action="" method="post">
    <input type="hidden" name="flag" value="<?= $edit_number ?? ''; ?>">
    名前 : <input type="text" name="name" placeholder="名前を入力してください" value="<?= $edit_name ?? ''; ?>"><br>
    コメント : <input type="text" name="comment" placeholder="コメントを入力してください" value="<?= $edit_comment ?? ''; ?>"><br>
    パスワード : <input type="password" name="post_pass" placeholder="パスワードを入力してください"><br>
    <input type="submit" name="submit">
</form>
<p>【削除フォーム】</p>
<form action="" method="post">
    投稿番号 : <input type="number" name="num_delete" placeholder="削除対象番号"><br>
    パスワード : <input type="password" name="delete_pass"><br>
    <input type="submit" name="delete" value="削除"> 
</form>
<p>【編集フォーム】</p>
<form action="" method="post">
    投稿番号 : <input type="number" name="num_edit" placeholder="編集対象番号"><br>
    パスワード : <input type="password" name="edit_pass"><br>
    <input type="submit" name="edit" value="編集">
</form>
<p>【投稿一覧】</p>

<?php 
// 名前とコメントが一行になるのは掲示板としてどうなの？って思うので、
// 後で表示するエリアを適切に定め、bootstrap等を用いて整形したい。
$sql = "SELECT * FROM board";
$result = $pdo -> query($sql);
$results = $result -> fetchAll();
foreach($results as $row) {
    echo $row['id'] . ",";
    echo $row['name'] . ",";
    echo $row['comment'] . ",";
    echo $row['created_at'];
    echo "<hr>";
}



?>

</body>
</html>
