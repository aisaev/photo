<?php
namespace photo\common;
require_once __DIR__.'/../application/PhotosetHelper.class.php';
$proc = new PhotosetHelper();
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
<style type="text/css">
body{
    padding:0;
    margin:0;
    background-color:black;
}
img{
    display:block;
    margin-left:auto;
    margin-right:auto;
}
</style>
<title></title>
<script src="/js/jquery.js"></script>
<script src="/js/config.js"></script>
<script src="/js/slideshow.js"></script>
<?php
echo $proc->get_data_file_name();
?>
</head>
<body>
<img id="photo" onload="adjustSize(this);" onclick="photoCarousel('right',true);">
</body>
</html>