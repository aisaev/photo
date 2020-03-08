<?php 
namespace photo\common;
require_once __DIR__.'/../application/ListHelper.class.php';
?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
<!-- link href="/bootstrap/css/bootstrap.css" rel="stylesheet" -->
<link href="/css/common.css" rel="stylesheet">
<style>
.new-year{background-color: #99ccff;cursor: pointer;}
</style>
<title></title>
<script src="/js/jquery.js"></script>
<!-- script src="/bootstrap/js/bootstrap.js"></script --> 
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
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
<?php include 'common_top.html'; ?>
<div class="container">
<table id="alist" class="table table-striped" style="width: auto; margin: auto;">
<tbody>
</tbody>
</table>
</div>
<div class="modal fade" id="slideshow" tabindex="-1" role="dialog" aria-labelledby="detLbl" aria-hidden="true" style="width:100%;">
	<div class="modal-dialog" role="document" style="min-width:100%;min-height:100%;">
		<div class="modal-content">
			<div class="modal-body" style="position: relative;">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position:absolute; top:5px; right: 5px; font-size: 3em;color: white;">
					<i class="fas fa-times-circle"></i></button>
				<div class="content-center">
				</div>		
			</div>	
		</div>
	</div>
</div>
</body>
</html>