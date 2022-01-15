<!DOCTYPE html>
<?php
session_start();
if (isset($_GET['p2'])) {
  $_SESSION['enteredPage2'] = true;
}
if (isset($_SESSION['enteredPage2'])) {
  //输出页面2.在页面2里，包含到页面3的链接如下
  echo "<a href=\"3.php\">返回</a>";
} else {
  //输出页面1，包含到页面2的链接如下
  echo "<a href='?p2='>返回</a>";
}
?>
<html>
  <head>
    <title>403 Forbidden</title>
    <script type="text/javascript">history.go(1);</script>
  </head>
</html>
<body>
  <h1>403 Forbidden</h1>
</body>