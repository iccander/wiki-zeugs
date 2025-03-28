function sendQS(){
  let qs  = $('#quickstatement').val().trim();
  qs = qs.split(/\n/).join('||');
  qs = encodeURIComponent(qs);
  const url = "https://quickstatements.toolforge.org/#v1="+qs;
  const win = window.open(url, '_blank');
  win.focus();
}

$('input.search-gnd').autocomplete({minLength:3,source : function(request, response) {
$.ajax({url:"https://lobid.org/gnd/search",dataType:"jsonp",
data:{filter:"type:Person",size:20,q:request.term,format:"json:suggest"},success:function(data) {response(data);}});},
select:function(event,ui) {$('#id').val(ui.item.id.slice(ui.item.id.lastIndexOf('/')+1));}});

var liste = document.getElementById("wikidata");
let output = document.getElementById("output");
let textfeld = document.getElementById("quickstatement");
let merker = output.innerHTML;
let last = "LAST";
liste.addEventListener("change", showSelected);
function showSelected(evt) {
    var slValue = liste.value;
    var slId = liste.selectedIndex;
    var slText = liste.options[slId].text;
    if (last === "LAST") {
		textfeld.value = textfeld.value.replace("CREATE\n", "");
		textfeld.value = textfeld.value.replace("CREATE", "");
	}
	if (slValue === "LAST") {
		textfeld.value = "CREATE\n" + textfeld.value;
		output.innerHTML = merker;
	} else 
		output.innerHTML = merker + " –&gt; <a href=\"https://www.wikidata.org/wiki/" + slValue + "\" target=\"_blank\" rel=\"noreferrer noopener\">" + slValue + "</a>";
	textfeld.value = textfeld.value.replaceAll(last, slValue);
	last = slValue;
}