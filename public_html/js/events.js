var app=APP_EVENT;
$(document).ready(function() {
	reloadOnFTH=false;
	$('#my-nav li.evt').addClass('active');
	var yr_curr=(new Date()).getFullYear();
	var s='';
	var len=events.length;
	var yr_last='';
	var html='';
	for(var i=0;i<len;i++) {
		var oe=new Event(events[i]);
		if(showSentimental || !oe.sentimental) {
			var tr=toTR(oe,yr_curr);
			if(yr_last!=oe.yr) {
				yr_last=oe.yr;
				html+='<tr><td class="new-year" colspan="3"><div onclick="toggleYr('+
						(oe.yr=='Pinned'?"'Pinned'":oe.yr)+',this);return false;">'+
						(oe.yr=='Pinned'?'<span class="glyphicon glyphicon-pushpin"></span>':oe.yr)+
						' <span class="glyphicon glyphicon-chevron-'+
						(tr.hasClass('recent')?'down':'right')+'"></div></td></tr>';
			}
			html+=tr[0].outerHTML;
		}
	}
	$('#alist tbody').html(html);
	//$('.navbar-brand').text(pg_title[lng]);
	$('td.new-year')
	$('.collapse').collapse('hide');
	$('.recent').collapse('show');
	delete events;
});

function toTR(o,yr_curr) {
	var is_recent = o.yr=='Pinned'||o.yr>=(yr_curr-1);
	var tr=$('<tr class="yr'+o.yr+' collapse'+(is_recent?' recent':'')+
			'"><td><a href="/event/'+o.id+'">'+
			(o.date_from=='Pinned'?'<span class="glyphicon glyphicon-pushpin"></span>':o.readableDates())+
			'</a></td><td class="d"></td></tr>');
	$("td.d",tr).text(lng==LNG_RU?o.desc_r:o.desc_e);
	return tr;
}

function toggleYr(year,el) {
	var span=$('span.glyphicon',el);
	$('tr.yr'+year).collapse('toggle');
	if(span.hasClass('glyphicon-chevron-right')) {
		span.removeClass('glyphicon-chevron-right').addClass('glyphicon-chevron-down');
	} else {
		span.removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-right');
	}
}