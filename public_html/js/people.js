var app=2;
$(document).ready(function() {
	initGlobal();
	reloadOnFTH=false;
	$('#my-nav li.ppl').addClass('active');
	var s='';
	var len=people.length;
	var html='';
	for(var i=0;i<len;i++) {
		var o=new Person(people[i]);
		if(!o.no_photos && (!o.sent || showSentimental)) {
			var tr=toTR(o);
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
	var tr=$('<tr><td><a href="/person/'+o.id+'" class="name"></a></td><td class="country"></td></tr>');
	$("a.name",tr).text(o.fullName());
	$("td.country",tr).text(o.countryName());
	return tr;
}
