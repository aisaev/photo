<?php 
namespace photo\common;
require_once __DIR__.'/../application/PhotosetHelper.class.php';
$proc = new PhotosetHelper();
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
<!-- link href="/bootstrap/css/bootstrap.css" rel="stylesheet" -->
<link href="/css/common.css" rel="stylesheet">
<title></title>
<script src="/js/jquery.js"></script>
<!-- script src="/bootstrap/js/bootstrap.js"></script --> 
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<script src="/js/jquery.lazy-master/jquery.lazy.min.js"></script>
<script src="/js/jquery.lazy-master/jquery.lazy.plugins.min.js"></script>
<script src="/js/config.js"></script>
<?php
echo $proc->get_data_file_name();
?>
<script src="/js/common.js"></script>
<script src="/js/photoset.js"></script>
</head>
<body>
<?php include 'common_top.html'; ?>
<div class="container" style="min-width: 100%;padding-left: 1em;padding-right: 1em;">
	<h1></h1>
	<div id="root">
	</div>
</div>
		
<div class="modal fade" id="photoDetails" tabindex="-1" role="dialog" aria-labelledby="detLbl" aria-hidden="true" style="width:100%;">
	<div class="modal-dialog" role="document" style="min-width:100%;min-height:100%;">
		<div class="modal-content">
			<div class="modal-body" style="position: relative;">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position:absolute; top:5px; right: 5px; font-size: 3em;color: white;">
					<i class="fas fa-times-circle"></i></button>
				<div class="content-center">
    				<div id="largePhoto">
    					<a class="carousel-control left" role="button"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span></a>
    					<a class='img'><img src=""></a>
    					<a class="carousel-control right" role="button"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span></a>
    				</div>
    				<div id="details" style="min-width: 30%;margin-top: 1em;margin-left:2em;">
    					<input id="photoid" type="hidden">
        				<dl class="dl-horizontal">
        					<dt class="evt">Event:</dt><dd class="evt"></dd>
        					<dt class="loc">Place:</dt><dd class="loc"></dd>
        					<dt class="ppl">People:</dt><dd class="ppl"></dd>
        					<dt class="cmt">Comment:</dt><dd class="cmt"></dd>
        					<dt class="ton">Taken on:</dt><dd class="ton"></dd>									
        				</dl>
	    				<div id="picfull" style="position:absolute;right:5px;bottom:5px;"></div>
    				</div>
				</div>		
			</div>	
		</div>
	</div>
</div>

</body>
</html>