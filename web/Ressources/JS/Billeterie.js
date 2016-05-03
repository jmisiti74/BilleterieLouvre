$(function () {
    $(document).tooltip();
	$("#closeTaReduit").hide();
	$("#helpTaReduit").hide();
	$("#openTaReduit").click(function(){
		$("#openTaReduit").hide();
		$("#helpTaReduit").show();
		$("#closeTaReduit").show();
	});
	$("#closeTaReduit").click(function(){
		$("#openTaReduit").show();
		$("#helpTaReduit").hide();
		$("#closeTaReduit").hide();
	});
});