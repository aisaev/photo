var id=getParameterByName('id');
var idseq=1;
var placeById = [];
var placeTree = [];
var rowTemplate='';
var hasErrors = false;

function AddNew()
{
	var elTR = $(rowTemplate);
	elTR.removeClass('parent').addClass("gis-child new");
	o={id:'$'+idseq++,p:id,dr:'',de:'',t:'0',cr:'',ce:''};
	elTR.addClass('l'+o.id);
	$(".p",elTR).val(o.p);
	$(".dr",elTR).val(o.dr);
	$(".de",elTR).val(o.de);
	$(".cr",elTR).val(o.cr);
	$(".ce",elTR).val(o.ce);
	$(".id",elTR).html('NEW<input type="hidden" value="'+o.id+'">');
	$(".t",elTR).val(o.t);
	$("tbody").append(elTR);

}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function prepPlaceData() {
 	var len = places.r.length;
 	placeById=[];
 	placeTree=[];
 	
 	//build tree and index
	for(var i=0;i<len;i++) {
	 	var o = places.r[i];
	 	if(o.cr) {} else {o.cr='';}
	 	if(o.ce) {} else {o.ce='';}
	 	o.text = (o.dr+(o.dr==o.de?'':" | "+o.de));
	 	placeById[o.id] = o;
	 	if(typeof placeTree[o.p] === 'undefined') placeTree[o.p] = [];
	 	placeTree[o.p].push(o.id);	 		
 	}
	for(var k in placeTree) {
		if(placeTree[k].length>1) {
			placeTree[k].sort(function(a,b){
				var oa=placeById[a];
				var ob=placeById[b];
				return (oa.de<ob.de?-1:1);
			});
		}
	} 
	o={id:0,dr:'World',de:'World',t:10,p:0,text:'World'};
	placeById[0]=o;
}

function SavePlaces() {
	//collect entries updated entries
	var oid=0;
	var updates=[], updHash=[];
	$(".places tr.gis").each(function(){
		oid=0;
		if($(this).hasClass("parent")) oid = id;
		else {
			if(!$(this).hasClass("new")) {
				oid = parseInt($('.id a',this).text());
			}
		}
		var oOld={id:0,p:false,dr:'',de:'',t:'0',cr:'',ce:''};
		if(oid!=0)
			oOld=placeById[oid];
		
		var oNew={
				id:oid==0?$('.id input',this).val():oid,
				p:parseInt($('.p',this).val()),
				dr:$('.dr',this).val(),
				de:$('.de',this).val(),
				t:$('.t',this).val(),
				cr:$('.cr',this).val(),
				ce:$('.ce',this).val()
			}
		var changed=false;
		if(oOld.dr==oNew.dr) {
			delete oNew.dr;
		} else {
			changed=true;
		}
		if(oOld.de==oNew.de) {
			delete oNew.de;
		} else {
			changed=true;
		}
		if(oOld.t==oNew.t) {
			delete oNew.t;
		} else {
			changed=true;
		}
		if(oOld.p==oNew.p) {
			delete oNew.p;
		} else {
			changed=true;
		}
		if(oOld.cr==oNew.cr) {
			delete oNew.cr;
		} else {
			changed=true;
		}
		if(oOld.ce==oNew.ce) {
			delete oNew.ce;
		} else {
			changed=true;
		}
		if(oNew.p==false) {
			//error
			alert("Invalid parent");
		}
		else if(changed) {
			updates.push(oNew);
			updHash[oNew.id] = oNew;
		}
	});
	if(updates.length>0) {
		$.post("api.php?op=edl",{u:updates},null,'json')
		.done(function(data){
			hasErrors=false;
			if(data['e']=='0') {
				for(var i=0;i<data['r'].length;i++) {
					var r=data['r'][i];
					if(r.id) { //success
						var oNew = updHash[r.o];
						if(r.o!=r.id) {
							//new
							oNew.id=r.id;
							places.r.push(oNew);
							placeById[oNew.id]=oNew;
						} else {
							var oOld = placeById[r.id];
							//just refresh
							location.reload(true);
						}
					} else if(r.m) {
						hasErrors=true;
						showMessage(r.m,'alert-danger','tr.l'+r.o+' .msg');
					}
				}
			} else {
				hasErrors=true;
				showMessage(data['m'],'alert-danger');				
			}
			if (!hasErrors) {
				prepPlaceData();
				render();
			}
		});
	}
	
}

function selectPlace(idNew)
{
	id=idNew;
	render();
}
function selectPlaceA(elA)
{
	id=parseInt($(elA).text());	
	render();
}

function render() {
	$(".parent .id").val(id);
	Cookies.set('id',id);
	var html_bc='';
	var o=placeById[id];
	var parent=false;
	
	//breadcrumb 
	if (!!o) {
		parent = o;
		var jqEl=$('<li class="active"></li>');
		jqEl.text(o.text);
		html_bc=jqEl[0].outerHTML;
		while(o.p!=o.id) {
			o=placeById[o.p];
			jqEl=$('<li><a onclick="selectPlace('+o.id+')"></a></li>');
			$('a',jqEl).text(o.text);
			html_bc=jqEl[0].outerHTML+html_bc;
		}
		$('.breadcrumb').html(html_bc);		
	}
	
	//table
	//children rows
	$(".gis-child").remove();
	var children=placeTree[parent.id];
	if(!!children) {
		var html='';
		for(var i=0;i<children.length;i++) {
			var elTR = $(rowTemplate);
			elTR.removeClass('parent').addClass("gis-child");
			o=placeById[children[i]];
			elTR.addClass('l'+o.id);
			$(".p",elTR).val(o.p);
			$(".dr",elTR).val(o.dr);
			$(".de",elTR).val(o.de);
			$(".cr",elTR).val(o.cr);
			$(".ce",elTR).val(o.ce);
			$(".id a",elTR).text(o.id);
			$(".t",elTR).val(o.t);
			$("tbody").append(elTR);
		}
		
	}
	//parent row
	if (parent.id!=0) {
		o=parent;
		$(".parent .p").val(o.p);
		$(".parent .dr").val(o.dr);
		$(".parent .de").val(o.de);
		$(".parent .cr").val(o.cr);
		$(".parent .ce").val(o.ce);
		$(".parent .t").val(o.t);
		$(".parent .id").html(id);
		$(".parent").show();
	} else {
		$(".parent").hide();
	}
}

function showMessage(msg,alertType,area='#msg') {
	var hAlert=$('<div class="alert '+alertType+' alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="msgcnt"></div>');
	$(".msgcnt",hAlert).text(msg);
	$(area).append(hAlert);
}

$(document).ready(function() {
	if(id==null||id=='') {
		id = Cookies.get('id');
		if(id===undefined)
			id=0;
	}
	else id=parseInt(id);
	var html='<option value="0">Select...</option>';
	for(var i=0;i<cfgGISTypes.length;i++) {
		var el=$("<option value='"+cfgGISTypes[i][0]+"'></option>");
		el.text(cfgGISTypes[i][1]);
		html+=el[0].outerHTML;
	}
	$("select.t").html(html);
	rowTemplate = $('.places tbody').html();
	prepPlaceData();
	render();
});
