var evtidx=[];
$(function(){
	if(audit.e==1) {
		$("body").text(audit.m);
		return false;
	}
	
	initPhotoDetails();
	var len = events.r.length;
	
	for(var i=0;i<len;i++) {
		var o=events.r[i];
		evtidx[o.i] = o;
	}
	
	var s='';
	var fn,id;
	for(var i=0;i<audit.r.nodb.length;i++) {
		s+=getIMG(audit.r.nodb[i]);
	} 
	$("#nodb").html(s);
	
	s='';
	for(var i=0;i<audit.r.hf.length;i++) {
		var o = audit.r.hf[i]
		s+=getIMG(o.i,o);
	} 
	$("#hf").html(s);
	
	s='';
	for(var i=0;i<audit.r.nof.length;i++) {
		s+=getInfo(audit.r.nof[i]);
	} 
	$("#nof").html(s);
});

function getIMG(id,o=null) {
	var fn=(''+(100000+id)).substring(1,6);
	var pfx=fn.substring(0,2);
	return '<div class="tmb"><a class="pic-det" onclick="showDetails('+id+');"><img src="full/'+pfx+'/'+fn+'.jpg" title="'+id+'"></a>'+(o==null?'':'<div>Event: '+o.e+'</div>')+'</div>';
	
}
function getInfo(o) {
    var el=$('<tbody><tr><td>'+o.i+'</td><td class="evt"></td><td>'+o.t+'</td><td class="cmt"></td></tr></tbody>');
    $('.evt',el).text(evtidx[o.e].r+' ('+o.e+')');
    return el.html();
}

function initPhotoDetails()
{
	var detLbl={'en':['Event','Place','People','Comment','Taken on'],
			'ru':["Событие","Место","Люди","Комментарий","Снято"]};
	var a=detLbl[lng];
	//labels on details
	$('dt.evt').text(a[0]+':');
	$('dt.loc').text(a[1]+':');
	$('dt.ppl').text(a[2]+':');
	$('dt.cmt').text(a[3]+':');
	$('dt.ton').text(a[4]+':');
	
	$("#photoDetails").on('show.bs.modal',function(){
		var w=$(window).width(),h=$(window).height();
		$("#photoDetails").css('max-height',h).css('max-width',w);
		$("#largePhoto img").css('max-height',h-60);
		$("#largePhoto img").css('max-width',w/3>500?Math.floor((w-60)*0.68):w-60);
		var dw=Math.floor((w-60)*0.3);
		if((MAX_PHOTO_W+dw)<w) {
			dw = w - MAX_PHOTO_W - 80;
			$("#details").css('width',dw);
		} else $("#details").css(dw>500?'width':'min-width',dw);
	});
}

function hideDetails() {
	$('#photoDetails').modal('hide');
	return false;
}


function showDetails(id) {
	if($("#photoDetails").is(":visible")) {
		hideDetails();
	}
	$("#photoid").val(id);
	var op=pl_idx[id];
	var fname=op.dir_name+'/'+op.file_name+'.jpg';
	var img=$("<a class='img' href='/full/"+fname+"' target='_PHOTO'><img onload='adjustSize(this);'></a>");
	$("#largePhoto a.img").replaceWith(img);
	$("#largePhoto img").attr('src','/pics/'+fname);
	//carousel init
	if(typeof op.idx !== 'undefined') {
		if(op.idx==0) {
			//1st photo, hide left arrow
			$("#largePhoto .carousel-control.left").hide();
		} else {
			$("#largePhoto .carousel-control.left").show().attr("onclick","showDetails("+photos[op.idx-1].i+")");
		}
		if(op.idx>=(photos.length-1)) {
			//last photo, hide right arrow
			$("#largePhoto .carousel-control.right").hide();
		} else {
			$("#largePhoto .carousel-control.right").show().attr("onclick","showDetails("+photos[op.idx+1].i+")");
		}
	}
	var evtDescr=el_idx[op.event].nameToShow();
	if(app!=APP_EVENT) {
		evtDescr = '<a href="/event/'+op.event+'">'+evtDescr+'</a>';
	}
	$("dd.evt").html(evtDescr);
	$("dd.loc").html(buildLocDesc(op.place));
	$("dd.ppl").html(buildPplList(op.people));
	(op.people.length>0?$("#details .ppl").show():$("#details .ppl").hide());
	var cmt=op.comment();
	if(cmt=='') $("#details .cmt").hide();
	else { 
		$("dd.cmt").html(cmt); 
		$("#details .cmt").show(); 
	}	
	(op.taken_on==null?$('.ton').hide():$("dd.ton").text(op.taken_on));
	//$("#picfull").html("<a href='/full/"+fname+"' target='_PHOTO'><img src='/img/full.png'></a>");
	$("#photoDetails").height(document.documentElement.clientHeight).modal('show');	
}