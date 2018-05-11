<?php 
namespace photo\common;
require_once __DIR__.'/../application/ListHelper.class.php';
?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link href="/bootstrap/css/bootstrap.css" rel="stylesheet">
<link href="/css/common.css" rel="stylesheet">
<style>
.new-year{background-color: #99ccff;cursor: pointer;}
</style>
<title></title>
<script src="/js/jquery.js"></script>
<script src="/bootstrap/js/bootstrap.js"></script>
<script src="/js/config.js"></script>
<script src="/js/common.js"></script>
<?php
if(!isset($_GET['op'])) {
?><script src="/js/err.js"></script><?php
} else {
    $proc = new ListHelper($_GET['op']);
?>
<script src="<?php echo $proc->data_uri; ?>"></script>
<script src="<?php echo $proc->proc_uri ?>"></script><?php 
}
?>
</head>
<body>
<div class="container">
	<nav id="common-nav" class="navbar navbar-default navbar-fixed-top"><?php include 'common_top.html'; ?></nav>
<table id="alist" class="table table-striped" style="width: auto; margin: auto;">
<tbody>
</tbody>
</table>
</div>
</body>
</html>