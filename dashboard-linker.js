(function($){
	$('ul.dashboard_linker_list a').not('a.editBtn').click(function(){
		window.open(this.href);
		return false;
	});
	if (typeof dashboard_linker_form_stat !== 'undefined' && dashboard_linker_form_stat === 'closable') {
		$('#dashboard_linker_add_form p.submit').append('<input type="button" id="dashboard_linker_add_form_close" value="' + dashboard_linker_strings.close + '" />');
		$('#dashboard_linker_add_form').hide().before('<p class="submit open"><input type="button" id="dashboard_linker_add_form_open" value="' + dashboard_linker_strings.open + '" /></p>');
		$('#dashboard_linker_add_form_close').click(function(){
			$('#dashboard_linker_add_form').hide();
			$('#Dashboard_linker p.open').show();
		});
		$('#dashboard_linker_add_form_open').click(function(){
			$('#dashboard_linker_add_form').show();
			$('#Dashboard_linker p.open').hide();
		});
	}
})(jQuery);
