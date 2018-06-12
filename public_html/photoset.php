<?php 
namespace photo\common;
require_once __DIR__.'/../application/PhotosetHelper.class.php';
$proc = new PhotosetHelper();
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link href="/bootstrap/css/bootstrap.css" rel="stylesheet">
<link href="/css/common.css" rel="stylesheet">
<title></title>
<script src="/js/jquery.js"></script>
<script src="/bootstrap/js/bootstrap.js"></script>
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
<div class="container" style='width: 100%;padding-left:10px;padding-right:5px;'>
<nav id="common-nav" class="navbar navbar-default navbar-fixed-top">
<?php include 'common_top.html'; ?>
</nav>
	<h1></h1>
	<div id="root">
	</div>
</div>	
<div class="modal" id="photoDetails" tabindex="-1" role="dialog" aria-labelledby="detLbl" style="width:100%;">
	<div class="modal-dialog" role="document" style="width:100%;height:100%;">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position:absolute; top:5px; right: 5px; font-size: 3em;color: white;"><span class="glyphicon glyphicon-remove-circle"></span></button>
				<div class="content-center">
    				<div id="largePhoto">
    					<a class="carousel-control left" role="button"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span></a>
    					<img src="">
    					<a class="carousel-control right" role="button"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span></a>
    				</div>
    				<div id="details" style="min-width: 30%;margin-top: 10px;">
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