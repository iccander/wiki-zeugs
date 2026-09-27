function sendQS(){
  let qs  = $('#quickstatement').val().trim();
  qs = qs.split(/\n/).join('||');
  qs = encodeURIComponent(qs);
  const url = "https://quickstatements.toolforge.org/#v1="+qs;
  const win = window.open(url, '_blank');
  win.focus();
}

$('input.search-gnd').autocomplete({minLength:3,source : function(request, response) {
$.ajax({url:"proxy.php",dataType:"json",
data:{url:"https://lobid.org/gnd/search",filter:"type:Person",size:20,q:request.term,format:"json:suggest"},success:function(data) {response(data);}});},
select:function(event,ui) {$('#id').val(ui.item.id.slice(ui.item.id.lastIndexOf('/')+1));}});

var liste = document.getElementById("wikidata");
let output = document.getElementById("output");
let textfeld = document.getElementById("quickstatement");
let merker = output ? output.innerHTML : "";
let last = "LAST";
if (liste) liste.addEventListener("change", showSelected);
function showSelected(evt) {
    var slValue = liste.value;
    var slId = liste.selectedIndex;
    var slText = liste.options[slId].text;
    if (last === "LAST" && textfeld) {
		textfeld.value = textfeld.value.replace("CREATE\n", "");
		textfeld.value = textfeld.value.replace("CREATE", "");
	}
	if (slValue === "LAST" && textfeld) {
		textfeld.value = "CREATE\n" + textfeld.value;
		if (output) output.innerHTML = merker;
	} else if (output) {
		output.innerHTML = merker + " –&gt; <a href=\"https://www.wikidata.org/wiki/" + slValue + "\" target=\"_blank\" rel=\"noreferrer noopener\">" + slValue + "</a>";
	}
	if (textfeld) textfeld.value = textfeld.value.replaceAll(last, slValue);
	last = slValue;
}