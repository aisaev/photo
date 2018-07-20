var evtidx=[];
$(function(){
	if(audit.e==1) {
		$("body").text(audit.m);
		return false;
	}
	
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
		s+=getIMG(audit.r.hf[i].i);
	} 
	$("#hf").html(s);
	
	s='';
	for(var i=0;i<audit.r.nof.length;i++) {
		s+=getInfo(audit.r.nof[i]);
	} 
	$("#nof").html(s);
});

function getIMG(id) {
	var fn=(''+(100000+id)).substring(1,6);
	var pfx=fn.substring(0,2);
	return '<div class="tmb"><img src="full/'+pfx+'/'+fn+'.jpg" title="'+id+'"></div>';
	
}
function getInfo(o) {
    var el=$('<tbody><tr><td>'+o.i+'</td><td class="evt"></td><td>'+o.t+'</td><td class="cmt"></td></tr></tbody>');
    $('.evt',el).text(evtidx[o.e].r+' ('+o.e+')');
    return el.html();
}