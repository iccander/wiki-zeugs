function sendQS(){
	const qs = $('#quickstatement').val().trim();
	if (!qs) return;
	const url = "https://quickstatements.toolforge.org/#v1=" + encodeURIComponent(qs.split('\n').join('||'));
	const win = window.open(url,'_blank','noopener,noreferrer');
	if (win) win.focus();
}

$('input.search-gnd').autocomplete({minLength:3,source : function(request, response) {
$.ajax({url:"proxy.php",dataType:"json",
data:{url:"https://lobid.org/gnd/search",filter:"type:Person",size:20,q:request.term,format:"json:suggest"},success:function(data) {response(data);}});},
select:function(event,ui) {$('#id').val(ui.item.id.slice(ui.item.id.lastIndexOf('/')+1));}});

const liste = document.getElementById("wikidata");
const output = document.getElementById("output");
const textfeld = document.getElementById("quickstatement");
const merker = output ? output.innerHTML : "";
let last = "LAST";
if (liste) liste.addEventListener("change",showSelected);
function showSelected(evt) {
    const slValue = liste.value;
    if (last === "LAST" && textfeld) textfeld.value = textfeld.value.replace(/^CREATE\r?\n?/gm,"");
	if (slValue === "LAST" && textfeld) {
		textfeld.value = "CREATE\n" + textfeld.value;
		if (output) output.innerHTML = merker;
	} else if (output) {
		output.innerHTML = merker + " &rightarrow; <a href=\"https://www.wikidata.org/wiki/" + slValue + "\" target=\"_blank\" rel=\"noreferrer noopener\">" + slValue + "</a>";
	}
	if (textfeld) textfeld.value = textfeld.value.replaceAll(last,slValue);
	last = slValue;
}
