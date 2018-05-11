class PhotoFile {
	constructor(json) {
		if (json) {
			this.fn = json['fn'];
			this.dt = json['dt'];
			this.lt = json['lt'];
			this.ln = json['ln'];
			this.loc=json['l'];
			this.ppl=json['p'];
			this.id = json['i']?parseInt(json['i']):0;
			this.cr=(json['cr']?json['cr']:null);
			this.ce=(json['ce']?json['ce']:null);
			this.lumix=/^P\d/.test(this.fn);
		}
	}
	
	render(dir,ppldir) {
		var el = $("div.file-template").clone();
		if(this.id!=0) {
			$("input[name='id']",el).val(this.id);
		}
		$("div.img",el).replaceWith('<img src="/unprocessed/'+dir+'/.thumb/300/'+this.fn+'" data-toggle="popover" data-full="/unprocessed/'+dir+'/.thumb/1000/'+this.fn+'">');
		$("h3",el).text(this.fn);
		$(".taken-on",el).text(this.dt);
		$("input[name='taken-on']",el).val(this.dt);
		if(this.lt!=0) {
			$(".taken-loc",el).html(' from <a target="GMAP" href="http://www.google.com/maps?q='+this.lt+','+this.ln+'">here</a>');
		}
		$(".placeid",el).val(this.loc);
		if(this.loc!=0)
			$(".placedescr",el).text(placeLookup[parseInt(this.loc)].text);
		
		var html='';
		var pplused=[];
		for(var i=0;i<ppldir.length;i++) {
			var pid = ppldir[i];
			var o = peopleLookup[pid];
			pplused[pid]=true;
			var optionEl = $("<option value='"+pid+"'"+(this.ppl.indexOf(pid)<0?'':' selected')+"></option>");
			optionEl.text(o.text);
			html+=optionEl[0].outerHTML;
		}
		for(var i=0;i<this.ppl.length;i++) {
			var pid = this.ppl[i];
			if(pplused[pid]){
				//do nothing
			} else {
				var o = peopleLookup[pid];
				var optionEl = $("<option value='"+pid+"' selected></option>");
				optionEl.text(o.text);
				html+=optionEl[0].outerHTML;
			}
		}
		$("select.pplid",el).html(html);
		if(this.cr!==null || this.ce!==null) {
			$('div.comment',el).show();
			$('input[name="cr"]',el).attr("value",this.cr==null?'':this.cr);
			$('input[name="ce"]',el).attr("value",this.ce==null?'':this.ce);
			$('.my-cmt-btn',el).remove();
		} 			
		return el.html();
	}
}

class PhotoDir {
	constructor(json) {
		this.dir = json['d'];
		this.files = [];
		for(var i=0;i<json['f'].length;i++) {
			this.files.push(new PhotoFile(json['f'][i]));
		}
		this.subdir = [];
		for(var i=0;i<json['s'].length;i++) {
			this.subdir.push(new PhotoDir(json['s'][i]));
		}
		this.evt=json['e'];
		this.loc=json['l'];
		this.ppl=json['p'];
	}
	
	render(isRoot) {
		if(!isRoot && this.files.length==0) return '';
		var el = $("div.dir-template").clone();
		var pplHTML='';
		if(this.files.length==0) {
			$("form",el).remove();
			$(".button-holder",el).remove();
		} else {
			$('.panel.dir',el).addClass('has-files');
		}
		if(this.evt==0 || this.loc==0) $('.btn-success',el).prop("disabled",true);
		if(this.evt==0) $('td.evt',el).hide();
		if(this.loc==0) $('td.loc',el).hide();
		if(this.ppl.length==0) $('td.ppl',el).hide();
		$(".panel-heading .dir",el).text(this.dir);
		$("input.eventid",el).val(this.evt);
		if(this.evt!=0)
			$(".eventdescr",el).text(eventLookup[parseInt(this.evt)].text);
		$("input.placeid",el).val(this.loc);
		if(this.loc!=0)
			$(".placedescr",el).text(placeLookup[parseInt(this.loc)].text);
		if(this.ppl.length>0) {
			pplHTML=buildPeopleOptions(this.ppl,true);
			$("select.pplid",el).html(pplHTML);
		}
		var html_files = '';
		var lumix=false;
		for(var i=0;i<this.files.length;i++) {
			var oFile=this.files[i];
			if(oFile.lumix) lumix=true;
			html_files +=oFile.render(this.dir,this.ppl);
		}
		var html_subdir = '';
		for(var i=0;i<this.subdir.length;i++) {
			html_subdir += this.subdir[i].render(false);
		}
		$('.panel-body',el).html(html_files+html_subdir);
		if(!isRoot&&!lumix) $(".adjtime",el).remove();
		return el.html();
	}
	
	updateDefaults() {
		
	}
}

var oRoot = false;
var eventLookup = [];
var peopleLookup = [];
var placeLookup = [];
var placeById = [];
var placeTree = [];
var modalCaller = false;
var saveBtn=[];

function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
    return '';
};

function openModal(e,isDir)
{
	var rebuild = false;
	if(isDir)
		modalCaller = $('form',$(e).closest('.panel-heading'));
	else
		modalCaller = $(e).closest('form');

	var currentPlace = $(".placeid",modalCaller).val();
	var currentPeople = $(".pplid",modalCaller).val();
	var isDir = modalCaller.hasClass('dir-defaults');
	if(isDir) {		
		$("#elp h4").replaceWith('<h4>'+$('.dir',modalCaller).text()+'</h4>');
		var eventId = $(".eventid", modalCaller).val(); 
		$("#eventsel").val(eventId).trigger("change");

	} else {
		//file data
		var dir = $(modalCaller).closest("div.panel");
		var dirNameLast = $("#modal_parent").val();
		var dirNameNow = $("h3.dir",dir).text();
		if(dirNameLast!=dirNameNow) {
			$("#modal_parent").val(dirNameNow);
			rebuild = true;
		}
		if(currentPlace == '0' && $("#placesel").val()=='0')
			currentPlace = $(".placeid",dir).val();
	}
	 
	if(currentPlace!='0' && currentPlace != $("#current_place").val()) {
		$("#placesel").html(buildPlacesOptions(currentPlace)).change();			
	}
	if(currentPeople!=null && currentPeople.length>0 || isDir)
		$("#peoplesel").val(currentPeople).trigger("change");	
	$("#elp").modal('show');
}

function renderCountrySelector() {
	var html='';
	for(var i=0;i<cfgCountries.length;i++) {
		var optionEl=$("<option value='"+cfgCountries[i][0]+"'></option>");
		optionEl.text(cfgCountries[i][1]==cfgCountries[i][2]?cfgCountries[i][1]:cfgCountries[i][1]+' | '+cfgCountries[i][2]);
		html+=optionEl[0].outerHTML;		
	}
	$("#ppledit-c").html(html).select2();
}

function renderEventsSelector(parent) {
	if(Object.keys(eventLookup).length > 0) return;
	
 	var len = events.r.length;
 	var seldata = [{id:0,text:''}];
 	
 	for(var i=0;i<len;i++) {		
 		var o = events.r[i];
 		if(!o.e) o.e=o.r;
 		if(o.s) {} else o.s=false;
 		o.df = [o.f.substr(0,4),o.f.substr(5,2),o.f.substr(8,2)];
 		var dr=o.f.replace(/\-/g,'.');
 		if(!o.t) {
 			o.t=o.f;
 			o.dt = o.df;
 		} else {
 			o.dt = [o.t.substr(0,4),o.t.substr(5,2),o.t.substr(8,2)];
 			if(o.dt[0]!=o.df[0]) {
 				dr='-'+o.dt.replace(/\-/g,'.');
 			} else if(o.dt[1]!=o.df[1]) {
 				dr = dr+'-'+o.t.substr(5).replace(/\-/g,'.');
 			} else if(o.dt[2]!=o.df[2]) {
 				dr+='-'+o.dt[2];
 			}
 		}
 		var oOption={id:o.i,text:"("+dr+") "+o.r+(o.r==o.e?'':" | "+o.e)};
 		seldata.push(oOption);
 		eventLookup[o.i] = oOption;
 	}
 	$("#eventsel").select2({data:seldata,theme:"bootstrap",dropdownParent: parent});
}

function buildPlacesOptions(parentLoc)
{
	$("#current_place").val(parentLoc);
	var allowedPlaces = [];
	allowedPlaces['0']=true;
	pickPlacesToShow(parentLoc,allowedPlaces);

 	var len = places.r.length;
 	var html='<option value="0">Not selected</option>';

 	for(var i=0;i<len;i++) { 		
 		var o = places.r[i];
 		if(parentLoc=='0' || allowedPlaces[o.id]) {
 			var el=$('<option value="'+o.id+'"'+(parentLoc==o.id?' selected':'')+'></option>');
 			el.text(placeLookup[o.id].text);
 			html+=el[0].outerHTML;
 		}
 	}
 	return html; 	
}

function prepPlaceData() {
 	var len = places.r.length;
 	
 	//build tree and index
	for(var i=0;i<len;i++) {
	 	var o = places.r[i];
	 	placeById[o.id] = o;
	 	if(typeof placeTree[o.p] === 'undefined') placeTree[o.p] = [];
	 	placeTree[o.p].push(o.id);	 		
 	}
	
 	for(var i=0;i<len;i++) { 		
 		var o = places.r[i];
 		var oP = placeById[o.p];
 		if (typeof oP == 'undefined' && o.p!='0') {
 			alert("Messed up parent "+o.p+" in node "+o.id);
 			return;
 		}
 		//use ^ in beginning of string to lookup places that start with this
 		var oOption={id:o.id,text:'^'+o.dr+(o.dr==o.de?'':" | ^"+o.de)+(o.p=="0"?'':'  (in '+oP.dr+(oP.dr==oP.de?'':" | "+oP.de)+')')};
 		placeLookup[o.id] = oOption;
 	}	
}

function prepModal() {
	$(".modal").on('hidden.bs.modal',function(event) {
		$(this).removeClass('fv-modal-stack');
		$('body').data('fv_open_modals',$('body').data('fv_open_modals') - 1);
	})
	.on('shown.bs.modal',function(event){
        // keep track of the number of open modals        
        if ( typeof( $('body').data( 'fv_open_modals' ) ) == 'undefined' )
        {
          $('body').data( 'fv_open_modals', 0 );
        }
        
        // if the z-index of this modal has been set, ignore.
        if ( $(this).hasClass( 'fv-modal-stack' ) )
        {
             return;
        }
        
        $(this).addClass( 'fv-modal-stack' );

        $('body').data( 'fv_open_modals', $('body').data( 'fv_open_modals' ) + 1 );
        
        $(this).css('z-index', 1040 + (10 * $('body').data( 'fv_open_modals' )));

        $( '.modal-backdrop' ).not( '.fv-modal-stack' )
             .css( 'z-index', 1039 + (10 * $('body').data( 'fv_open_modals' )));


        $( '.modal-backdrop' ).not( 'fv-modal-stack' )
             .addClass( 'fv-modal-stack' ); 

	});
}

function prepPeopleData() {
	var len = people.r.length;
	
	for(var i=0;i<len;i++) {
		var o=people.r[i];
		var optionEl = $("<option value='"+o.id+"'></option>");
		var nameR = o.n+(o.aka?" AKA "+o.aka:'');
		var nameE = o.ne+(o.akae?" AKA "+o.akae:'');
		var optionData = {id:o.id,text:nameR+(nameR==nameE?'':' | '+nameE)+(o.c?' (from '+o.c+')':'')};
		peopleLookup[o.id] = optionData;
	}
	
}

function sqlDate(dt) {
	var s=dt.getFullYear()+'-';
	var dd=dt.getMonth()+1;
	if(dd<10) dd='0'+dd;
	s+=dd+'-';
	dd=dt.getDate();
	if(dd<10) dd='0'+dd;
	s+=dd+' ';
	dd=dt.getHours();
	if(dd<10) dd='0'+dd;
	s+=dd+':';
	dd=dt.getMinutes();
	if(dd<10) dd='0'+dd;
	s+=dd+':';
	dd=dt.getSeconds();
	if(dd<10) dd='0'+dd;
	return s+dd;
	
}

function editEvent(id=0) {
	if(id==0) {
		$("#evtedit form input").val('');
	} else {
		
	}
	$("#evtedit form input[name='i']").val(id);
	$("#evtedit").modal('show');
}

function editPerson(id=0) {
	$("#ppledit form input[name='id']").val(id);
	$("#ppledit").modal('show');
}

function addMinutes(date,minutes) {
	return new Date(date.getTime()+minutes*60000);
}

function adjustTimes(el) {
	var timeOffset=$(el).val();
	var elDir=$(el).closest("div.dir");
	var lumix=false;
	$("form.file-data",elDir).each(function(){
		if(/^P\d/.test($('h3',this).text())) { //lumix
			lumix = true;
			var dt=new Date($("input[name='taken-on']",this).val());
			dt=addMinutes(dt,timeOffset);			
			var s=sqlDate(dt);
			$("span.taken-on",this).text(s);
		}
	});
	if(lumix) {
		$('div.photo',elDir).sort(function(a,b){
			var dtA = new Date($("span.taken-on",a).text());
			var dtB = new Date($("span.taken-on",b).text());
			return dtA.getTime()>dtB.getTime();
		}).appendTo(elDir);
	}
}

function buildPeopleOptions(ppltab,allSelect,id_only=true,ppldir=[]) {
	var html = '';
	for(var i=0;i<ppltab.length;i++) {
		var id;
		if (id_only) {
			id = ppltab[i];			
		}			
		else
			id = ppltab[i].id;
		var optionEl = $("<option value='"+id+"'"+(allSelect?' selected':'')+"></option>");
		optionEl.text(peopleLookup[id].text);
		html+=optionEl[0].outerHTML;
	}
	if(id_only && ppldir.length>0) { //file data
		var lcl_pplfile=[];
		var lcl_ppldir=[];
		for(var i=0;i<ppltab.length;i++) {
			lcl_pplfile[ppltab[i]]=true;
		}
		for(var i=0;i<ppldir.length;i++) {
			if(lcl_pplfile[ppldir[i]]) {
				
			} else {
				lcl_ppldir[ppldir[i]]=true;
			}				
		}
		$("#peoplesel option").each(function(){
			var id=$(this).val();
			if(lcl_ppldir[id]) html+=this.outerHTML;
		});
	}
	
	return html;
}

function renderPeopleSelector(parentControl) {
	$("#peoplesel").html(buildPeopleOptions(people.r,false,false));
 	$("#peoplesel").select2({
 		tags:true,
 		theme:"bootstrap",
 		dropdownParent: parentControl,
 		minimumResultsForSearch:5
 	});
}

function renderPlacesSelector(parentControl) {
	$("#placesel").html(buildPlacesOptions(0));
 	$("#placesel").select2({
 		allowClear: true,
 		placeholder: "Select place",
 		theme:"bootstrap",
 		dropdownParent: parentControl,
 		minimumResultsForSearch:5
 	}).on('select2:select',function(e){
 		var currentPlace = e.params.data.id;
 		if(placeTree[currentPlace].length>0) {
 			$("#placesel").html(buildPlacesOptions(currentPlace)).change();
 		} 			
 	}).on('select2:unselect',function(e){
 		$("#placesel").html(buildPlacesOptions("0")).change();
 	});
}

function pickPlacesToShow(parentLoc,result) {
	result[parentLoc]=true;
	var children = placeTree[parentLoc];
	if(typeof children != 'undefined') {
		for(var i=0;i<children.length;i++)
			pickPlacesToShow(children[i],result);
	}
}

function collectDirData(el) {
	var dirCollected = {};
	$(".dir-defaults",el).each(function(){		
		dirCollected = {
				d:$(".dir",this).text(),
				e:$("input.eventid",this).val(),
				l:$("input.placeid",this).val(),
				p:$("select.pplid",this).val(),
				f:[]
			};
		//file data
		var seq=1;
		$("form.file-data",el).each(function(){
			var fileCollected = {
					f:$("h3",this).text(),
					l:$("input.placeid",this).val(),
					p:$("select.pplid",this).val(),
					s:seq++,
					dt:$(".taken-on",this).text(),
					i:$("input[name='id']").val() 
			};
			if($("input[name='cr']",this).is(":visible")) {
				fileCollected.cr = $("input[name='cr']",this).val();
				fileCollected.ce = $("input[name='ce']",this).val();				
			}
			dirCollected.f.push(fileCollected);
		});

	});
	return dirCollected;	
}

function callSave(o,op) {
	$.ajax({
		type: "POST",
		url:"api.php?op="+op,
		data: o,
		dataType: 'json'
	})
	.done(function(data){
		var hAlert=$('<div class="alert alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="msgcnt"></div>');
		if((''+data['e'])=='1') {
			$(".msgcnt",hAlert).text(data['m']);
			hAlert.addClass('alert-danger');
		} else {
			$(".msgcnt",hAlert).text('Saved');
			hAlert.addClass('alert-success');
			if(op=='db') {
				//remove panel
				var skip=false;
				$('h3.dir').each(function(){
					if(skip) return;
					skip=($(this).text()==o.d);
					if(skip) $(this).closest('.has-files').remove();
				});
			}
		}
		$("#msg").append(hAlert);			
	})
	.fail(function(jqxhr,textStatus,errorThrown){
		var hAlert=$('<div class="alert alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="msgcnt"></div>');
		$(".msgcnt",hAlert).text(textStatus);
		hAlert.addClass('alert-danger');
		$("#msg").append(hAlert);
	})
	.always(function() {
		$(".loader").hide();
	});
}

function Save(op)
{
	$(".loader h2").text("Saving draft...");
	$(".loader").show();
	//dir data
	var o={d:[]};
	$('.panel.dir.has-files').each(function(){
		o.d.push(collectDirData(this));
	});
	
	callSave(o,op);
}

function SaveEventDir(el) {
	$(".loader h2").text("Saving in DB");
	$(".loader").show();
	var o = collectDirData($(el).closest('.dir.has-files'));
	callSave(o,'db');
}

function saveEvent() {
	$.post("api.php?op=edevt",$("#evtedit form").serialize(),function(data){},'json')
		.done(function(data){
			//returns pplID
			if(data['e']=='0') {
				//all good, got back ID
				var o = data['r'];
				events.r.unshift(o); //add to beginning
				events.r.sort(function(a,b){
					if(a.f<b.f) return 1;
					if(a.f>b.f) return -1;
					if(a.t<b.t) return 1;
					return -1;}
				);
				eventLookup=[];
				$("#eventsel").select2("destroy");
				$("#eventsel").html('');
				renderEventsSelector($('#elp'));
				$("#evtedit").modal('hide');
			} else {
				alert(data['m']);
			}
		})
		.fail(function(jqXHR,textStatus,errorThrown){
			alert(errorThrown);
		});
}

function savePerson() {
	$.post("edit_api.php?op=edppl",$("#ppledit form").serialize())
		.done(function(data){
			//returns pplID
			if(data['e']=='0') {
				//all good, got back ID
				var o = {
					id:data['id'],
					n:$("#ppledit form input[name='n']").val(),
					a:$("#ppledit form input[name='a']").val(),
					ne:$("#ppledit form input[name='ne']").val(),
					ae:$("#ppledit form input[name='ae']").val(),
					c:$("#ppledit form select[name='c']").val()
				};
				people.r.push(o);
				var optionEl = $("<option value='"+o.id+"'></option>");
				var nameR = o.n+(o.a?" AKA "+o.a:'');
				var nameE = o.ne+(o.ae?" AKA "+o.ae:'');
				var optionData = {id:o.id,text:nameR+(nameR==nameE?'':' | '+nameE)+(o.c?' (from '+o.c+')':'')};
				peopleLookup[o.id] = optionData;
				var optionEl = $("<option value='"+o.id+"' selected></option>");
				optionEl.text(optionData.text);				
				$("#peoplesel").append(optionEl).trigger("change");
				$("#ppledit").modal('hide');
			} 
		})
		.fail(function(jqXHR,textStatus,errorThrown){
			alert(textStatus);
		});
}

function showComments(el) {
	var form = $(el).closest("form");
	var parent = $(el).parent();
	$(parent).remove();
	$("div.comment",form).show();
}

function ToggleFiles(e) {
	var div = $(e).closest("div.has-files");
	if($('.panel-body',div).is(":hidden")) $('.panel-body,.dir-defaults tbody',div).show();
	else $('.panel-body,.dir-defaults tbody',div).hide();
}

function updateCaller() {
	var selEvent = $("#eventsel option:selected");
	var l_evt = selEvent.val();
	$(".eventdescr", modalCaller).text(selEvent.text());
	$(".eventid", modalCaller).val(l_evt);
	
	var selPlace = $("#placesel option:selected");
	var l_loc = selPlace.val();
	$(".placedescr", modalCaller).text(selPlace.text());
	$(".placeid", modalCaller).val(l_loc);


	//rebuild people selector of caller
	var l_ppl = $("#peoplesel").val();
	var html='';
	$("#peoplesel option:selected").each(function(){
		html+=this.outerHTML;
	});
	
	var isDir = modalCaller.hasClass('dir-defaults');
	var dir = $(modalCaller).closest("div.panel");
	if(isDir) {
		var dirEl = $(modalCaller).closest(".panel-heading");
		$('.pplid',modalCaller).html(html).val($("#peoplesel").val()).change();
		$("form.file-data select.pplid",dir).each(function(){
			var v = $(this).val();
			if($(this).html()=='' || v==null || v.length==0) $(this).html(html).change();
		});
		$('.btn-success',dirEl).prop("disabled", l_evt=='0' || l_loc=='0');
		if(l_evt=='0') $('td.evt',dirEl).hide(); else $('td.evt',dirEl).show();
		if(l_loc=='0') $('td.loc',dirEl).hide(); else $('td.loc',dirEl).show();
		if(l_ppl==null || l_ppl.length==0) $('td.ppl',dirEl).hide(); else $('td.ppl',dirEl).show();

	} else {
		$('.pplid',modalCaller).val(l_ppl).change();
	}
	$("#elp").modal('hide');
	
}

$(document).ready(function() {
	
	prepPlaceData();
	prepPeopleData();
	var modal = $('#elp');
	renderEventsSelector(modal);
	renderPeopleSelector(modal);
	renderPlacesSelector(modal);
	renderCountrySelector();
	prepModal();
	var offset_min=getUrlParameter('m');
	if(offset_min=='') offset_min=0;
	$(".loader h2").text("Getting data...");
	$.getJSON( "api.php?op=upl&m="+offset_min )
	.done(function(data){
		$(".loader h2").text("Rendering data...");
		if(data['e']=="1") {
			var hAlert=$('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><span class="msgcnt"></div>');
			$(".msgcnt",hAlert).text(data['m']);
			$("#msg").append(hAlert);			
		} else {
			oRoot = new PhotoDir(data['r']);
			$('#root').append(oRoot.render(true));
			oRoot.updateDefaults();
			$('[data-toggle="popover"]').popover({
				container: 'body',
				html: true,
				placement: 'auto',
				trigger: 'hover',
				delay: {show:700},
				content: function() {
					var url=$(this).data('full');
					return '<img src="'+url+'">';
				}
			});
			$(".dir-defaults select.pplid").select2({tags:true,width:'100%'});
			$(".file-data select.pplid").select2({tags:true});
		}
		$(".loader").hide();
	})	
	.fail(function(jqXHR,textStatus,errorThrown){
		$(".loader h2").text("Error while retrieving data");
	});
});
